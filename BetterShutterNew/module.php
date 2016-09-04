<?

// TODO: 
// - Highlight correct movement button.
//   Simplest solution might be to poll for position status and check if it
//   moved in a defined delta time.

require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterShutterNew extends BetterBase {

    const MoveControlUp = 1;
    const MoveControlStop = 2;
    const MoveControlDown = 3;

    protected function GetModuleName()
    {
        return "BSN";
    }

    // Properties
    private function PositionStatusIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function PositionIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function UpDownIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function StopIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function WindowStatusIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    // Variables
    private function Enabled() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    private function MoveControl() {        
        return new IPSVarInteger($this->InstanceID(), __FUNCTION__);
    }   

    private function PositionLimit() {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    private function IsAtLimitedPosition() {
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
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
    private function UpDownTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

    private function StopTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

    private function WindowTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

	public function Create() 
    {
		parent::Create();		

        $this->PositionIdProp()->Register();
        $this->PositionStatusIdProp()->Register();
        $this->UpDownIdProp()->Register();
        $this->StopIdProp()->Register();
        $this->WindowStatusIdProp()->Register();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->WindowStatusLink()->Register("Fenster Status", $this->WindowStatusIdProp()->Value());

        $this->PositionStatusLink()->Register("Positions Status", $this->PositionStatusIdProp()->Value());

        $this->PositionLimit()->Register("Positions Limit", "~Shutter");
        $this->PositionLimit()->EnableAction();

        $this->IsAtLimitedPosition()->Register();
        $this->TargetPosition()->Register();

        $this->Enabled()->Register("Aktiviert", "~Switch");
        $this->Enabled()->EnableAction();
        $this->Enabled()->SetValue(true);

        $this->MoveControl()->Register("Bewegen", "BS_MoveControl");
        $this->MoveControl()->EnableAction();

        $this->UpDownTrigger()->Register("", $this->UpDownIdProp()->Value(), 'BSN_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
        
        $this->StopTrigger()->Register("", $this->StopIdProp()->Value(), 'BSN_StopEvent($_IPS[\'TARGET\']);', IPSEventTrigger::TypeUpdate);

        $this->WindowTrigger()->Register("", $this->WindowStatusIdProp()->Value(), 'BSN_WindowEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);

        $this->InitValues();
	}

    private function InitValues()
    {
        $this->MoveControl()->SetValue(-1);

        $this->IsAtLimitedPosition()->SetValue($this->PositionStatus() == $this->PositionLimit()->Value());

        // If at the limit position, assume it should be at 100.
        if($this->PositionStatus() == $this->PositionLimit()->Value())
            $this->TargetPosition()->SetValue(100);
        else 
            $this->TargetPosition()->SetValue($this->PositionStatus());
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

    public function UpDownEvent($moveDown)
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
    }

    public function StopEvent()
    {
        $this->Log("StopEvent()");

        if(!$this->Enabled()->Value())
            return;

        $instanceId = $this->InstanceID();
        $script = "BSN_UpdateTargetPosition($instanceId);";
        $this->CheckPositionTimer()->StartTimer(0.5, $script);
    }

    public function UpdateTargetPosition()
    {
        $this->Log("UpdateTargetPosition()");
        $this->TargetPosition()->SetValue($this->PositionStatus());
    }

    public function WindowEvent($open)
    {
        $this->Log("WindowEvent(open:$open)");

        if(!$this->Enabled()->Value())
            return;

        if($open)
        {
            if($this->PositionStatus() > $this->PositionLimit()->Value())
                $this->MoveToLimitedDown();
        }
        else if($this->IsAtLimitedPosition()->Value())
            $this->MoveDown();
    }

    private function MoveShutter($moveControl)
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
        $this->MoveTo($this->PositionLimit()->Value());
        $this->IsAtLimitedPosition()->SetValue(true);
    }

    private function MoveUp()
    {
        $this->Move(false);
    }

    private function MoveDown()
    {
        $this->Move(true);
    }

    private function Move($down)
    {
        $this->Log("Move(down:$down)");
        $upDownId = $this->UpDownIdProp()->Value();
        EIB_Switch(IPS_GetParent($upDownId), $down);
        $this->IsAtLimitedPosition()->SetValue(false);
    }

    private function MoveTo($pos)
    {
        $this->Log("MoveTo(pos:$pos)");
        $positionId = $this->PositionIdProp()->Value();
        EIB_Scale(IPS_GetParent($positionId), $pos);
    }

    private function Stop()
    {
        $this->Log("Stop()");
        $stopId = $this->StopIdProp()->Value();
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

    private function UpdatePositionLimit($value)
    {
        $this->PositionLimit()->SetValue($value);

        if($this->IsWindowOpen() && $this->IsAtLimitedPosition()->Value())
        {
            $this->MoveToLimitedDown();
        }
    }
}

?>