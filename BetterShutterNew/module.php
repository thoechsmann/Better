<?
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

    private function ShouldBeDown() {
        return new IPSVarBoolean($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    // Links
    private function WindowStatusLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    // Triggers
    private function UpDownTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

    private function WindowTrigger() {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

	public function Create() 
    {
		parent::Create();		

        $this->PositionIdProp()->Register();
        $this->UpDownIdProp()->Register();
        $this->StopIdProp()->Register();
        $this->WindowStatusIdProp()->Register();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->WindowStatusLink()->Register("Fenster Status", $this->WindowStatusIdProp()->Value());

        $this->PositionLimit()->Register("Positions Limit", "~Shutter");
        $this->PositionLimit()->EnableAction();

        $this->Enabled()->Register("Aktiviert", "~Switch");
        $this->Enabled()->EnableAction();
        $this->Enabled()->SetValue(true);

        $this->MoveControl()->Register("Bewegen", "BS_MoveControl");
        $this->MoveControl()->EnableAction();

        $this->ShouldBeDown()->Register();

        $this->UpDownTrigger()->Register("", $this->UpDownIdProp()->Value(), 'BSN_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
        $this->WindowTrigger()->Register("", $this->WindowStatusIdProp()->Value(), 'BSN_WindowEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);
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
                $this->PositionLimit()->SetValue($Value);
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function UpDownEvent($moveDown)
    {
        if(!$this->Enabled()->Value())
            return;

        $this->Log("UpDownEvent(moveDown:$moveDown)");

        if($moveDown && $this->IsWindowOpen()) // window open
        {
            $this->MoveShutterToLimitedDown();
        }

        $this->ShouldBeDown()->SetValue($moveDown);
    }

    public function WindowEvent($open)
    {
        if(!$this->Enabled()->Value())
            return;

        $this->Log("WindowEvent(open:$open)");

        if($open)
        {
            if($this->ShouldBeDown()->Value() == true)
                $this->MoveShutterToLimitedDown();
        }
        else
        {
            $this->MoveShutterToShouldBePosition();
        }
    }

    private function MoveShutter($moveControl)
    {
        switch($moveControl)
        {
            case MoveControlUp:
                $this->MoveUp();
                break;
            case MoveControlStop:
                $this->Stop();
                break;
            case MoveControlDown:
                $this->MoveDown();
                break;
        }
    }

    private function MoveShutterToLimitedDown()
    {
        $this->Log("MoveShutterToLimitedDown");
        $this->MoveTo($this->PositionLimit()->Value());
    }

    private function MoveShutterToShouldBePosition()
    {
        $this->Log("MoveShutterToShouldBePosition, shouldBeDown:" . $this->ShouldBeDown()->Value());
        $this->Move($this->ShouldBeDown()->Value());
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
        $upDownId = $this->UpDownIdProp()->Value();
        EIB_Switch(IPS_GetParent($upDownId), $down);
    }

    private function MoveTo($pos)
    {
        $positionId = $this->PositionIdProp()->Value();
        EIB_Scale(IPS_GetParent($positionId), $pos);
    }

    private function Stop()
    {
        $stopId = $this->StopIdProp()->Value();
        EIB_Switch(IPS_GetParent($stopId), true);
    }

    private function IsWindowOpen()
    {
        $windowId = $this->WindowStatusIdProp()->Value();
        return GetValue($windowId);
    }

}
?>