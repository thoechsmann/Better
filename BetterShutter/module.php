<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterShutter extends BetterBase {

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("positionId", 0);
        $this->RegisterPropertyInteger("upDownId", 0);
        $this->RegisterPropertyInteger("stopId", 0);
        $this->RegisterPropertyInteger("windowId", 0);
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        // Cleanup
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childId)
        {
            $this->DeleteObject($childId);
        }

        $this->RegisterLink("windowStatus", "Fenster", $this->ReadPropertyInteger("windowId"), 0);
        $this->RegisterLink("upDown", "Hoch/Runter", $this->ReadPropertyInteger("upDownId"), 1);
        $this->RegisterLink("position", "Position", $this->ReadPropertyInteger("positionId"), 1);
        $this->RegisterLink("stop", "Stopp", $this->ReadPropertyInteger("stopId"), 1);

        $limitId = $this->RegisterVariableInteger("positionLimit", "Limit");
        $twighlightCheckId = $this->RegisterVariableBoolean("twighlightCheck", "Dämmerungsautomatik");
        $this->RegisterVariableBoolean("shouldBeDown", "shouldBeDown");

        // Scheduled Event
        $scheduler = $this->RegisterScheduler("Wochenplan");
        IPS_SetIcon($scheduler, "Calendar");
        IPS_SetPosition($scheduler, 5);
        IPS_SetEventScheduleGroup($scheduler, 0, 127); // Mo - Fr (1 + 2 + 4 + 8 + 16)
        IPS_SetEventScheduleAction($scheduler, 0, "Offen", 0x00FF00, "BH_SetMode(\$_IPS['TARGET'], 1);");
        IPS_SetEventScheduleAction($scheduler, 1, "Geschlossen", 0x0000FF, "BH_SetMode(\$_IPS['TARGET'], 2);");

        $scheduler = $this->RegisterScheduler("Wochenplan_schulfrei", "Wochenplan (schulfrei)");
        IPS_SetIcon($scheduler, "Calendar");
        IPS_SetPosition($scheduler, 5);
        IPS_SetEventScheduleGroup($scheduler, 0, 127); // Mo - Fr (1 + 2 + 4 + 8 + 16)
        IPS_SetEventScheduleAction($scheduler, 0, "Offen", 0x00FF00, "BH_OpenShutter(\$_IPS['TARGET']);");
        IPS_SetEventScheduleAction($scheduler, 1, "Geschlossen", 0x0000FF, "BH_CloseShutter(\$_IPS['TARGET']);");

        $downTriggerId = $this->RegisterTrigger("upDownTrigger", $this->ReadPropertyInteger("upDownId"), 'BS_DownEvent($_IPS[\'TARGET\']);', 4);
        IPS_SetEventTriggerValue($downTriggerId, true);

        $downTriggerId = $this->RegisterTrigger("openCloseTrigger", $this->ReadPropertyInteger("windowId"), 'BS_WindowEvent($_IPS[\'TARGET\']);', 1);

	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "Boost":
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function OpenShutter()
    {
        $shouldBeDownId = $this->GetIDForIdent("shouldBeDown");
        $shouldBeDown = SetValue($shouldBeDownId, false);

        // send close to KNX
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), false);
    }

    public function CloseShutter()
    {   
        $shouldBeDownId = $this->GetIDForIdent("shouldBeDown");
        $shouldBeDown = SetValue($shouldBeDownId, true);

        // send close to KNX
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), true);
    }

    public function DownEvent()
    {
        $windowId = $this->ReadPropertyInteger("windowId");
        if(GetValue($id) == false) // window closed
        {
            return;
        }

        IPS_LogMessage("BetterShutter", "Window opened. Limiting shutter movement");

        $positionId = $this->ReadPropertyInteger("positionId");
        $positionLimit = $this->ReadPropertyInteger("positionLimit");
        EIB_Scale(IPS_GetParent($positionId), $positionLimit);
    }

    public function WindowEvent()
    {
        $id = $this->ReadPropertyInteger("windowId");
        if(GetValue($id) == false) // window closed
        {
            return;
        }

        IPS_LogMessage("BetterShutter", "Window opened. Limiting shutter movement");

        $positionId = $this->ReadPropertyInteger("positionId");
        $positionLimit = $this->ReadPropertyInteger("positionLimit");
        EIB_Scale(IPS_GetParent($positionId), $positionLimit);
    }

}
?>