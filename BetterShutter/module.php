<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterShutter extends BetterBase {

    private $isDayId = 18987;
    // private $isDayId = 52946;

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("positionId", 0);
        $this->RegisterPropertyInteger("upDownId", 0);
        $this->RegisterPropertyInteger("stopId", 0);
        $this->RegisterPropertyInteger("windowId", 0);

        $this->RegisterPropertyInteger("statusPositionId", 0);

        $this->RegisterPropertyInteger("otherUpDownId1", 0);
        $this->RegisterPropertyInteger("otherUpDownId2", 0);
        $this->RegisterPropertyInteger("otherUpDownId3", 0);

        $this->RegisterPropertyInteger("positionLimit", 70);
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RegisterLink("windowStatus", "Fenster", $this->ReadPropertyInteger("windowId"), 0);
        $this->RegisterLink("upDown", "Hoch/Runter", $this->ReadPropertyInteger("upDownId"), 1);
        $this->RegisterLink("position", "Position", $this->ReadPropertyInteger("positionId"), 1);
        $this->RegisterLink("stop", "Stopp", $this->ReadPropertyInteger("stopId"), 1);

        $this->RegisterVariableBoolean("twighlightCheck", "Dämmerungsautomatik", "~Switch");
        $this->EnableAction("twighlightCheck");
        $this->SetValueForIdent("twighlightCheck", true);

        $openOnDawnId = $this->RegisterVariableBoolean("openOnDawn", "Bei Morgendämmerung öffnen");
        IPS_SetHidden($openOnDawnId, true);

        $shouldBeDownId = $this->RegisterVariableBoolean("shouldBeDown", "shouldBeDown");
        IPS_SetHidden($shouldBeDownId, true);

        $upLimitId = $this->RegisterVariableInteger("upLimit", "Frühstes öffnen");
        $upLimitDate = new DateTime("9:00");
        SetValue($upLimitId, $upLimitDate->getTimestamp());

        $this->RegisterVariableInteger("upLimitHoliday", "Frühstes öffnen (schulfrei)");

        $this->RegisterVariableInteger("downLimit", "Spätestes schliessen");

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

        $upDownId = $this->ReadPropertyInteger("upDownId");
        $this->RegisterTrigger("upDownTrigger", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);

        $upDownId = $this->ReadPropertyInteger("otherUpDownId1");
        if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);
        $upDownId = $this->ReadPropertyInteger("otherUpDownId2");
        if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);
        $upDownId = $this->ReadPropertyInteger("otherUpDownId3");
        if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);

        $this->RegisterTrigger("openCloseTrigger", $this->ReadPropertyInteger("windowId"), 'BS_WindowEvent($_IPS[\'TARGET\']);', 1);

        $dawnTriggerId = $this->RegisterTrigger("dawnTrigger", $this->isDayId, 'BS_OnDawn($_IPS[\'TARGET\']);', 4);
        IPS_SetEventTriggerValue($dawnTriggerId, true);

        $sunsetTriggerId = $this->RegisterTrigger("sunsetTrigger", $this->isDayId, 'BS_OnSunset($_IPS[\'TARGET\']);', 4);
        IPS_SetEventTriggerValue($sunsetTriggerId, false);

        // If shutter is up, we assume $shouldBeDown = false at module creation time.
        $statusPositionId = $this->ReadPropertyInteger("statusPositionId");
        $shouldBeDown = GetValue($statusPositionId) != 0;
        $this->SetValueForIdent("shouldBeDown", $shouldBeDown);
	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "twighlightCheck":
                $this->SetValueForIdent($Ident, $Value);
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function OpenShutter() // called by scheduler
    {
        $twighlightCheck = $this->GetValueForIdent("twighlightCheck");
        $isDay = GetValue($this->isDayId);

        if($twighlightCheck && !$isDay)
        {
            // Do not open, but wait for dawn.
            $this->SetValueForIdent("openOnDawn", true);
            return;
        }

        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), false);
    }

    public function CloseShutter() // called by scheduler
    {   
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), true);
    }

    public function OnDawn()
    {
        $twighlightCheck = $this->GetValueForIdent("twighlightCheck");
        $openOnDawn = $this->GetValueForIdent("openOnDawn");

        if($twighlightCheck && $openOnDawn)
        {
            $upDownId = $this->ReadPropertyInteger("upDownId");
            EIB_Switch(IPS_GetParent($upDownId), false);
        }

        $this->SetValueForIdent("openOnDawn", false);
    }

    public function OnSunset()
    {
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), true);
    }

    public function UpDownEvent($moveDown)
    {
        $windowId = $this->ReadPropertyInteger("windowId");        
        $windowOpen = GetValue($windowId);

        if($moveDown && $windowOpen) // window open
        {
            $this->MoveShutterToLimitedDown();
        }

        $this->SetValueForIdent("shouldBeDown", $moveDown);
    }

    public function WindowEvent()
    {
        $windowId = $this->ReadPropertyInteger("windowId");

        if(GetValue($windowId) == true) // window open
        {
            if($this->GetValueForIdent("shouldBeDown"))
                $this->MoveShutterToLimitedDown();
        }
        else
        {
            $this->MoveShutterToShouldBePosition();
        }
    }

    private function MoveShutterToLimitedDown()
    {
        $positionId = $this->ReadPropertyInteger("positionId");
        $positionLimit = $this->ReadPropertyInteger("positionLimit");
        EIB_Scale(IPS_GetParent($positionId), $positionLimit);        
    }

    private function MoveShutterToShouldBePosition()
    {
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), $this->GetValueForIdent("shouldBeDown"));
    }

}
?>