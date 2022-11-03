<?

// TODO: 
// P2: Highlight correct movement button.
//     Simplest solution might be to poll for position status and check if it
//     moved in a defined delta time.

declare(strict_types=1);
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");
require_once(__DIR__ . "/ShutterControl.php");

class BetterShutterNew extends BetterBase {

    const ShutterControlCount = 3;

    const MoveControlUp = 1;
    const MoveControlStop = 2;
    const MoveControlDown = 3;

    protected function GetModuleName()
    {
        return "BSN";
    }

    private function ShutterControls()
    {
        return new ShutterControlArray($this, BetterShutterNew::ShutterControlCount);
    }

    // Properties
    private function PositionStatusIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function PositionIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function WindowStatusIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    // Variables
    private function Enabled() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    private function PositionIsLimited() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    private function MoveControl() {        
        return new IPSVarInteger($this->InstanceID(), __FUNCTION__);
    }   

    private function PositionLimit() {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    private function TargetPosition() {
        return new IPSVarInteger($this->InstanceID(), __FUNCTION__);
    }

    // Events
    private function CheckPositionTimer()
    {
        return new IPSEventCyclic($this->InstanceID(), __FUNCTION__);
    }

    // Links
    private function WindowStatusLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function PositionStatusLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    // Triggers
    private function WindowTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

	public function Create() 
    {
		parent::Create();		

        $this->PositionIdProp()->Register();
        $this->PositionStatusIdProp()->Register();
        $this->WindowStatusIdProp()->Register();

        $this->ShutterControls()->RegisterProperties();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->WindowStatusLink()->Register("Fenster Status", $this->WindowStatusIdProp()->Value());

        $this->PositionStatusLink()->Register("Positions Status", $this->PositionStatusIdProp()->Value());

        $this->PositionLimit()->Register("Positions Limit", "~Shutter");
        $this->EnableAction($this->PositionLimit()->Ident());

        $this->TargetPosition()->Register();
        $this->PositionIsLimited()->Register();

        $this->Enabled()->Register("Aktiviert", "~Switch");
        $this->Enabled()->SetValue(true);
        $this->EnableAction($this->Enabled()->Ident());

        $this->MoveControl()->Register("Bewegen", "BS_MoveControl");
        $this->EnableAction($this->MoveControl()->Ident());

        $this->WindowTrigger()->Register("", $this->WindowStatusIdProp()->Value(), 'BSN_WindowEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);

        $this->ShutterControls()->RegisterTriggers();

        $this->InitValues();
	}

    private function InitValues()
    {
        $this->MoveControl()->SetValue(BetterShutterNew::MoveControlStop);

        if($this->PositionStatusIdProp()->Value() == 0)
            return;
        
        // If at the limit position, assume it should be at 100.
        if($this->PositionStatus() == $this->PositionLimit()->Value())
        {
            $this->PositionIsLimited()->SetValue(true);
            $this->TargetPosition()->SetValue(100);
        }
        else 
        {
            $this->PositionIsLimited()->SetValue(false);
            $this->TargetPosition()->SetValue($this->PositionStatus());
        }
    }

    public function RequestAction($Ident, $Value) 
    {
        switch($Ident) {
            case $this->Enabled()->Ident():
                $this->Enabled()->SetValue($Value);
                break;

            case $this->MoveControl()->Ident():
                $this->MoveShutter($Value);
                break;

            case $this->PositionLimit()->Ident():
                $this->UpdatePositionLimit($Value);
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

  public function SetPositionEvent(int $position)
  {
    $this->Log("SetPositionEvent(position:$position)");
  }  

    public function UpDownEvent(bool $moveDown)
    {
        $this->Log("UpDownEvent(moveDown:$moveDown)");

        if(!$this->Enabled()->Value())
            return;

        if($moveDown)
            $this->TargetPosition()->SetValue(100);
        else
            $this->TargetPosition()->SetValue(0);

        if($moveDown && $this->IsWindowOpen()) // window open
        {
            $this->MoveToLimitedDown();
        }
        else
        {
            $this->PositionIsLimited()->SetValue(false);
        }
    }

    public function StopEvent()
    {
        $this->Log("StopEvent()");

        if(!$this->Enabled()->Value())
            return;

        $instanceId = $this->InstanceID();
        $script = "BSN_UpdateTargetPosition($instanceId);";
        $this->CheckPositionTimer()->StartTimer(1, $script);
    }

    public function UpdateTargetPosition()
    {
        $this->Log("UpdateTargetPosition()");
        $this->TargetPosition()->SetValue($this->PositionStatus());

        // do not stop if moving to limited down.        
        if($this->PositionIsLimited()->Value())
        {
            $this->MoveToLimitedDown();
        }
    }

    public function WindowEvent(bool $open)
    {
        $this->Log("WindowEvent(open:$open)");

        if(!$this->Enabled()->Value())
            return;

        if($open)
        {
            if($this->TargetPosition()->Value() > $this->PositionLimit()->Value())
                $this->MoveToLimitedDown();
        }
        else if($this->PositionIsLimited()->Value())
        {
            $this->MoveToTarget();
        }
    }

    private function MoveShutter(int $moveControl)
    {
        switch($moveControl)
        {
            case BetterShutterNew::MoveControlUp:
                $this->MoveUp();
                break;
            case BetterShutterNew::MoveControlStop:
                $this->Stop();
                break;
            case BetterShutterNew::MoveControlDown:
                $this->MoveDown();
                break;
        }
    }

    private function MoveToLimitedDown()
    {
        $this->Log("MoveToLimitedDown");
        $this->PositionIsLimited()->SetValue(true);
        $this->MoveTo($this->PositionLimit()->Value());
    }

    private function MoveToTarget()
    {
        $this->Log("MoveToTarget");
        $this->PositionIsLimited()->SetValue(false);
        $this->MoveTo($this->TargetPosition()->Value());
    }

    private function MoveUp()
    {
        $this->Move(false);
    }

    private function MoveDown()
    {
        $this->Move(true);
    }

    private function Move(bool $down)
    {
        $this->Log("Move(down:$down)");        
        $upDownId = $this->ShutterControls()->At(0)->UpDownId();
        EIB_Switch(IPS_GetParent($upDownId), $down);
    }

    private function MoveTo(int $pos)
    {
        $this->Log("MoveTo(pos:$pos)");
        $positionId = $this->PositionIdProp()->Value();
        EIB_Scale(IPS_GetParent($positionId), $pos);
    }

    private function Stop()
    {
        $this->Log("Stop()");
        $stopId = $this->ShutterControls()->At(0)->StopId();
        EIB_Switch(IPS_GetParent($stopId), true);
    }

    private function IsWindowOpen()
    {
        $windowId = $this->WindowStatusIdProp()->Value();
        return GetValue($windowId);
    }

    private function PositionStatus()
    {
        $posStatusId = $this->PositionStatusIdProp()->Value();
        return GetValue($posStatusId);
    }

    private function UpdatePositionLimit(int $value)
    {
        $this->PositionLimit()->SetValue($value);

        if($this->IsWindowOpen())
        {
            $this->MoveToLimitedDown();
        }
    }
}

?>