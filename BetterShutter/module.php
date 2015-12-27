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

        $this->RegisterPropertyInteger("otherUpDownId1", 0);
        $this->RegisterPropertyInteger("otherUpDownId2", 0);
        $this->RegisterPropertyInteger("otherUpDownId3", 0);

        $this->RegisterPropertyInteger("positionLimit", 70);
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RemoveAll();

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

        $upLimitId = $this->RegisterVariableString("upLimit", "Frühstes öffnen");
        SetValue($upLimitId, "7:30");
        $this->EnableAction("upLimit");

        $upLimitHoliday = $this->RegisterVariableString("upLimitHoliday", "Frühstes öffnen (schulfrei)");
        SetValue($upLimitHoliday, "9:00");
        $this->EnableAction("upLimitHoliday");

        $downLimit = $this->RegisterVariableString("downLimit", "Spätestes schliessen");
        SetValue($downLimit, "22:00");
        $this->EnableAction("downLimit");

        // Create Scheduler
        $scheduler = $this->RegisterScheduler("Wochenplan");
        IPS_SetIcon($scheduler, "Calendar");
        IPS_SetPosition($scheduler, 5);
        IPS_SetEventScheduleGroup($scheduler, 0, 127); // Mo - Fr (1 + 2 + 4 + 8 + 16)
        IPS_SetEventScheduleAction($scheduler, 0, "Offen", 0x00FF00, "BS_ScheduledOpen(\$_IPS['TARGET'], false);");
        IPS_SetEventScheduleAction($scheduler, 1, "Offen (Feiertag)", 0xFFFF00, "BS_ScheduledOpen(\$_IPS['TARGET'], true);");
        IPS_SetEventScheduleAction($scheduler, 2, "Geschlossen", 0x0000FF, "BS_ScheduledClose(\$_IPS['TARGET']);");
        IPS_SetHidden($scheduler, true);
        $this->UpdateSchedulers();

        $upDownId = $this->ReadPropertyInteger("upDownId");
        $this->RegisterTrigger("upDownTrigger", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 0);

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

        $this->UpdateSchedulers();
	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "twighlightCheck":
                $this->SetValueForIdent($Ident, $Value);
                break;

            case "upLimit":
            case "upLimitHoliday":
            case "downLimit":
                $this->SetValueForIdent($Ident, $Value);
                $this->UpdateSchedulers();
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function ScheduledOpen($isHolidayCheck) // called by scheduler
    {
        IPS_LogMessage("BetterShutter", "ScheduledOpen");
   
        if($this->IsTodayHoliday() != $isHolidayCheck)
            return;

        IPS_LogMessage("BetterShutter", "ScheduledOpen2");

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

    public function ScheduledClose() // called by scheduler
    {   
        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), true);

        $this->SetValueForIdent("openOnDawn", false);
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
        IPS_LogMessage("BetterShutter", "OnSunset event");

        $windowId = $this->ReadPropertyInteger("windowId");        
        $windowOpen = GetValue($windowId);

        if($windowOpen)
        {
            $this->MoveShutterToLimitedDown();
        }
        else
        {
            $upDownId = $this->ReadPropertyInteger("upDownId");
            EIB_Switch(IPS_GetParent($upDownId), true);
        }
    }

    public function UpDownEvent($moveDown)
    {
        IPS_LogMessage("BetterShutter", "UpDownEvent event");
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
        IPS_LogMessage("BetterShutter", "WindowEvent event");
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

    private function UpdateSchedulers()
    {
        $upLimit = $this->GetValueForIdent("upLimit");
        $upLimitDate = new DateTime($upLimit);

        $upLimitHoliday = $this->GetValueForIdent("upLimitHoliday");
        $upLimitHolidayDate = new DateTime($upLimitHoliday);

        $downLimit = $this->GetValueForIdent("downLimit");
        $downLimitDate = new DateTime($downLimit);

        $scheduler = $this->GetIDForIdent("Wochenplan");

        IPS_SetEventActive($scheduler, true);
        IPS_SetEventScheduleGroupPoint($scheduler, 0, 0, $upLimitDate->format("H"), $upLimitDate->format("i"), 0, 0);
        IPS_SetEventScheduleGroupPoint($scheduler, 0, 1, $upLimitHolidayDate->format("H"), $upLimitHolidayDate->format("i"), 0, 1);
        IPS_SetEventScheduleGroupPoint($scheduler, 0, 2, $downLimitDate->format("H"), $downLimitDate->format("i"), 0, 2);
    }

    private function MoveShutterToLimitedDown()
    {
        IPS_LogMessage("BetterShutter", "MoveShutterToLimitedDown");

        $positionId = $this->ReadPropertyInteger("positionId");
        $positionLimit = $this->ReadPropertyInteger("positionLimit");
        EIB_Scale(IPS_GetParent($positionId), $positionLimit);        
    }

    private function MoveShutterToShouldBePosition()
    {
        IPS_LogMessage("BetterShutter", "MoveShutterToShouldBePosition");

        $upDownId = $this->ReadPropertyInteger("upDownId");
        EIB_Switch(IPS_GetParent($upDownId), $this->GetValueForIdent("shouldBeDown"));
    }

}
?>