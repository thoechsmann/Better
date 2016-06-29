<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterShutterNew extends BetterBase {

    protected function GetModuleName()
    {
        return "BSN";
    }

    // Properties
    private function PositionIdProp()
    {        
        return new RegisterPropertyInteger($this->module, __FUNCTION__);
    }   

    private function UpDownIdProp()
    {        
        return new RegisterPropertyInteger($this->module, __FUNCTION__);
    }   

    private function StopIdProp()
    {        
        return new RegisterPropertyInteger($this->module, __FUNCTION__);
    }   

    private function WindowStatusIdProp()
    {        
        return new RegisterPropertyInteger($this->module, __FUNCTION__);
    }   

    // Variables
    private function PositionLimit()
    {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    private function ShouldBeDown()
    {
        return new IPSVarBool($this->InstanceID(), parent::PersistentPrefix . __FUNCTION__);
    }

    // Links
    private function PositionLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function UpDownLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function StopLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function WindowStatusLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    // Triggers
    private function UpDownTrigger()
    {
        return new IPSTrigger($this->InstanceID(), __FUNCTION__);
    }

    private function WindowTrigger()
    {
        return new IPSTrigger($this->InstanceID(), __FUNCTION__);
    }

	public function Create() 
    {
		parent::Create();		

        $this->PositionIdProp()->Register();
        $this->UpDownIdProp()->Register();
        $this->StopIdProp()->Register();
        $this->WindowStatusIdProp()->Register();

        // $this->RegisterPropertyInteger("otherUpDownId1", 0);
        // $this->RegisterPropertyInteger("otherUpDownId2", 0);
        // $this->RegisterPropertyInteger("otherUpDownId3", 0);
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

        $this->UpDownTrigger()->Register("", $this->UpDownIdProp()->Value(), 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
        $this->WindowTrigger()->Register("", $this->WindowStatusIdProp()->Value(), 'BS_WindowEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);

        // $this->RegisterVariableBoolean("twighlightCheck", "Dämmerungsautomatik", "~Switch");
        // $this->EnableAction("twighlightCheck");
        // $this->SetValueForIdent("twighlightCheck", true);

        // $this->RegisterVariableBoolean("off", "Aus", "~Switch");
        // $this->EnableAction("off");
        // $this->SetValueForIdent("off", false);

        // $openOnDawnId = $this->RegisterVariableBoolean("openOnDawn", "Bei Morgendämmerung öffnen");
        // IPS_SetHidden($openOnDawnId, true);



        // $upLimitId = $this->RegisterVariableString("upLimit", "Frühstes öffnen");
        // SetValue($upLimitId, "7:30");
        // $this->EnableAction("upLimit");

        // $upLimitHoliday = $this->RegisterVariableString("upLimitHoliday", "Frühstes öffnen (schulfrei)");
        // SetValue($upLimitHoliday, "9:00");
        // $this->EnableAction("upLimitHoliday");

        // $downLimit = $this->RegisterVariableString("downLimit", "Spätestes schliessen");
        // SetValue($downLimit, "22:00");
        // $this->EnableAction("downLimit");

        // Create Scheduler
        // $scheduler = $this->RegisterScheduler("Wochenplan");
        // IPS_SetIcon($scheduler, "Calendar");
        // IPS_SetPosition($scheduler, 5);
        // IPS_SetEventScheduleGroup($scheduler, 0, 127); // Mo - Fr (1 + 2 + 4 + 8 + 16)
        // IPS_SetEventScheduleAction($scheduler, 0, "Offen", 0x00FF00, "BS_ScheduledOpen(\$_IPS['TARGET'], false);");
        // IPS_SetEventScheduleAction($scheduler, 1, "Offen (Feiertag)", 0xFFFF00, "BS_ScheduledOpen(\$_IPS['TARGET'], true);");
        // IPS_SetEventScheduleAction($scheduler, 2, "Geschlossen", 0x0000FF, "BS_ScheduledClose(\$_IPS['TARGET']);");
        // IPS_SetHidden($scheduler, true);
        // $this->UpdateSchedulers();

        // $upDownId = $this->UpDownIdProp->Value();
        // $this->RegisterTrigger("upDownTrigger", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 0);

        // $upDownId = $this->ReadPropertyInteger("otherUpDownId1");
        // if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);
        // $upDownId = $this->ReadPropertyInteger("otherUpDownId2");
        // if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);
        // $upDownId = $this->ReadPropertyInteger("otherUpDownId3");
        // if($upDownId != 0) $this->RegisterTrigger("otherUpDownTrigger1", $upDownId, 'BS_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', 1);

        // $this->RegisterTrigger("openCloseTrigger", $this->WindowIdProp->Value(), 'BS_WindowEvent($_IPS[\'TARGET\']);', 1);

        // $dawnTriggerId = $this->RegisterTrigger("dawnTrigger", $this->isDayId, 'BS_OnDawn($_IPS[\'TARGET\']);', 4);
        // IPS_SetEventTriggerValue($dawnTriggerId, true);

        // $sunsetTriggerId = $this->RegisterTrigger("sunsetTrigger", $this->isDayId, 'BS_OnSunset($_IPS[\'TARGET\']);', 4);
        // IPS_SetEventTriggerValue($sunsetTriggerId, false);

        // $this->UpdateSchedulers();
	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            // case "twighlightCheck":
            //     $this->SetValueForIdent($Ident, $Value);
            //     break;

            // case "upLimit":
            // case "upLimitHoliday":
            // case "downLimit":
            //     $this->SetValueForIdent($Ident, $Value);
            //     $this->UpdateSchedulers();
            //     break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function ScheduledOpen($isHolidayCheck) // called by scheduler
    {
        // IPS_LogMessage("BetterShutter", "ScheduledOpen");
   
        // if($this->IsTodayHoliday() != $isHolidayCheck)
        //     return;

        // IPS_LogMessage("BetterShutter", "ScheduledOpen2");

        // $twighlightCheck = $this->GetValueForIdent("twighlightCheck");
        // $isDay = GetValue($this->isDayId);

        // if($twighlightCheck && !$isDay)
        // {
        //     // Do not open, but wait for dawn.
        //     $this->SetValueForIdent("openOnDawn", true);
        //     return;
        // }

        // $upDownId = $this->ReadPropertyInteger("upDownId");
        
        // $off = $this->GetValueForIdent("off");
        // if(!$off)
        //     EIB_Switch(IPS_GetParent($upDownId), false);
    }

    public function ScheduledClose() // called by scheduler
    {   
        // $upDownId = $this->ReadPropertyInteger("upDownId");
        
        // $off = $this->GetValueForIdent("off");
        // if(!$off)
        //     EIB_Switch(IPS_GetParent($upDownId), true);

        // $this->SetValueForIdent("openOnDawn", false);
    }

    public function OnDawn()
    {
        // $twighlightCheck = $this->GetValueForIdent("twighlightCheck");
        // $openOnDawn = $this->GetValueForIdent("openOnDawn");

        // if($twighlightCheck && $openOnDawn)
        // {
        //     $upDownId = $this->ReadPropertyInteger("upDownId");

        //     $off = $this->GetValueForIdent("off");
        //     if(!$off)
        //         EIB_Switch(IPS_GetParent($upDownId), false);
        // }

        // $this->SetValueForIdent("openOnDawn", false);
    }

    public function OnSunset()
    {
        // IPS_LogMessage("BetterShutter", "OnSunset event");

        // $windowId = $this->ReadPropertyInteger("windowId");        
        // $windowOpen = GetValue($windowId);

        // if($windowOpen)
        // {
        //     $this->MoveShutterToLimitedDown();
        // }
        // else
        // {
        //     $upDownId = $this->ReadPropertyInteger("upDownId");

        //     $off = $this->GetValueForIdent("off");
        //     if(!$off)
        //         EIB_Switch(IPS_GetParent($upDownId), true);
        // }

        // $this->SetValueForIdent("shouldBeDown", true);
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

    private function UpdateSchedulers()
    {
        // $upLimit = $this->GetValueForIdent("upLimit");
        // $upLimitDate = new DateTime($upLimit);

        // $upLimitHoliday = $this->GetValueForIdent("upLimitHoliday");
        // $upLimitHolidayDate = new DateTime($upLimitHoliday);

        // $downLimit = $this->GetValueForIdent("downLimit");
        // $downLimitDate = new DateTime($downLimit);

        // $scheduler = $this->GetIDForIdent("Wochenplan");

        // IPS_SetEventActive($scheduler, true);
        // IPS_SetEventScheduleGroupPoint($scheduler, 0, 0, $upLimitDate->format("H"), $upLimitDate->format("i"), 0, 0);
        // IPS_SetEventScheduleGroupPoint($scheduler, 0, 1, $upLimitHolidayDate->format("H"), $upLimitHolidayDate->format("i"), 0, 1);
        // IPS_SetEventScheduleGroupPoint($scheduler, 0, 2, $downLimitDate->format("H"), $downLimitDate->format("i"), 0, 2);
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