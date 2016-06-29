<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterShutterNew extends BetterBase {

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
    private function PositionLimit() {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    private function ShouldBeDown() {
        return new IPSVarBoolean($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    // Links
    private function PositionLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function UpDownLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function StopLink() {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

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
		
        $this->PositionLink()->Register("Position", $this->PositionIdProp()->Value());
        $this->UpDownLink()->Register("Hoch/Runter", $this->UpDownIdProp()->Value());
        $this->StopLink()->Register("Stopp", $this->StopIdProp()->Value());
        $this->WindowStatusLink()->Register("Fenster Status", $this->WindowStatusIdProp()->Value());

        $this->PositionLimit()->Register("Positions Limit");
        $this->ShouldBeDown()->Register();

        $this->UpDownTrigger()->Register("", $this->UpDownIdProp()->Value(), 'BSN_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
        $this->WindowTrigger()->Register("", $this->WindowStatusIdProp()->Value(), 'BSN_WindowEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);
	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function UpDownEvent($moveDown)
    {
        $this->Log("UpDownEvent(moveDown:$moveDown)");

        if($moveDown && $this->IsWindowOpen()) // window open
        {
            $this->MoveShutterToLimitedDown();
        }

        $this->ShouldBeDown()->SetValue($moveDown);
    }

    public function WindowEvent($open)
    {
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

    private function MoveShutterToLimitedDown()
    {
        $this->Log("MoveShutterToLimitedDown");

        $positionId = $this->PositionIdProp()->Value();

        EIB_Scale(IPS_GetParent($positionId), $this->PositionLimit()->Value());
    }

    private function MoveShutterToShouldBePosition()
    {
        $this->Log("MoveShutterToShouldBePosition, shouldBeDown:" . $this->ShouldBeDown()->Value());

        $upDownId = $this->UpDownIdProp()->Value();

        EIB_Switch(IPS_GetParent($upDownId), $this->ShouldBeDown()->Value());
    }

    private function IsWindowOpen()
    {
        $windowId = $this->WindowStatusIdProp()->Value();
        return GetValue($windowId);
    }
}
?>