<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterHeating extends BetterBase {

	public function UpdateWindow() 
    {
        // IPS_LogMessage("BetterHeating", "update");

        // Check window State
        $windowOpenId = $this->GetIDForIdent("WindowOpen");
        $openWindowCount = 0;

        $maxWindows = 7;
        for($i = 1; $i <= $maxWindows; $i++)
        {
            $id = $this->ReadPropertyInteger("window" . $i . "InstanceID");
            if($id!=0 && GetValue($id) === true)
            {
                $openWindowCount++;
            }
        }

        IPS_SetHidden($windowOpenId, $openWindowCount == 0);
    }

    public function UpdateHeatingMode() 
    {
        // Check Heating Mode
        $mode = $this->GetValueForIdent("modeInstanceID");
        $CurrentTargetTempId = $this->GetIDForIdent("CurrentTargetTemp");
        $TargetComfortTempId = $this->GetIDForIdent("TargetComfortTemp");

        IPS_SetHidden($CurrentTargetTempId, $mode == 1);        
        IPS_SetHidden($TargetComfortTempId, $mode != 1);   
    }

    public function UpdateBoost() 
    {
        $boostId = $this->GetIDForIdent("Boost");
        $boostTime = $this->GetValueForIdent("BoostTime");

        $boostTime--;
        $this->SetValueForIdent("BoostTime", $boostTime);

        if($boostTime <= 0)
        {
            $this->DeactivateBoost();
        }
        else
        {
            IPS_SetName($boostId, "Boost ($boostTime Minuten)");
        }
    }

    public function UpdatePresence() 
    {
    }

    public function SetMode($mode)
    {
        EIB_Scale(IPS_GetParent($this->ReadPropertyInteger("modeInstanceID")), $mode);
    }

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("currentTempInstanceID", 0);
        $this->RegisterPropertyInteger("currentTargetTempInstanceID", 0);
        $this->RegisterPropertyInteger("controlValueInstanceID", 0);
        $this->RegisterPropertyInteger("targetTempComfortInstanceID", 0);
        $this->RegisterPropertyInteger("modeInstanceID", 0);
        $this->RegisterPropertyInteger("boostInstanceID", 0);

        $this->RegisterPropertyInteger("window1InstanceID", 0);
        $this->RegisterPropertyInteger("window2InstanceID", 0);
        $this->RegisterPropertyInteger("window3InstanceID", 0);
        $this->RegisterPropertyInteger("window4InstanceID", 0);
        $this->RegisterPropertyInteger("window5InstanceID", 0);
        $this->RegisterPropertyInteger("window6InstanceID", 0);
        $this->RegisterPropertyInteger("window7InstanceID", 0);

        $this->RegisterPropertyInteger("presenceInstanceID", 0);
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RegisterVariableString("WindowOpen", "Fenster ist geöffnet -> Heizung aus", "", 0);

        $this->RegisterLink("CurrentTemp", "Temperatur", $this->ReadPropertyInteger("currentTempInstanceID"), 1);
        $this->RegisterLink("Mode", "Modus", $this->ReadPropertyInteger("modeInstanceID"), 2);
        $this->RegisterLink("TargetComfortTemp", "Soll Temperatur (Komfort)", $this->ReadPropertyInteger("targetTempComfortInstanceID"), 3);
        $this->RegisterLink("CurrentTargetTemp", "Soll Temperatur", $this->ReadPropertyInteger("currentTargetTempInstanceID"), 3);
        $this->RegisterLink("ControlValue", "Stellwert", $this->ReadPropertyInteger("controlValueInstanceID"), 10);

        if($this->ReadPropertyInteger("boostInstanceID") != 0)
        {
            $profileName = "BH_Boost";
            @IPS_DeleteVariableProfile($profileName);
            IPS_CreateVariableProfile($profileName, 0);
            IPS_SetVariableProfileAssociation($profileName, true, 'AN', '', 0xFF0000); 
            IPS_SetVariableProfileAssociation($profileName, false, 'AUS', '', -1); 
            
            $boostId = $this->RegisterVariableBoolean("Boost", "Boost", $profileName, 4);
            IPS_SetIcon($boostId, "Flame");
            $this->EnableAction("Boost");

            $boostTimeId = $this->RegisterVariableInteger("BoostTime", "BoostTime");
            IPS_SetHidden($boostTimeId, true);
        }

        // Scheduled Event
        $scheduler = $this->RegisterScheduler("Wochenplan");
        IPS_SetIcon($scheduler, "Calendar");
        IPS_SetPosition($scheduler, 5);
        IPS_SetEventScheduleGroup($scheduler, 0, 127); //Mo - Fr (1 + 2 + 4 + 8 + 16)
        IPS_SetEventScheduleAction($scheduler, 0, "Komfort", 0xFF0000, "BH_SetMode(\$_IPS['TARGET'], 1);");
        IPS_SetEventScheduleAction($scheduler, 1, "Standby", 0xFFFF00, "BH_SetMode(\$_IPS['TARGET'], 2);");
        IPS_SetEventScheduleAction($scheduler, 2, "Nacht", 0x0000FF, "BH_SetMode(\$_IPS['TARGET'], 3);");

        // Window triggers
        $maxWindows = 7;
        for($i = 1; $i <= $maxWindows; $i++)
        {
            $windowId = $this->ReadPropertyInteger("window" . $i . "InstanceID");
            if($windowId != 0)
            {
                $this->RegisterTrigger("Window" . $i . "Trigger", $windowId, 'BH_UpdateWindow($_IPS[\'TARGET\']);');
            }
        }

        // Mode trigger
        $this->RegisterTrigger("HeatingModeTrigger", $this->ReadPropertyInteger("modeInstanceID"), 'BH_UpdateHeatingMode($_IPS[\'TARGET\']);');

        // Presence trigger
        if($this->ReadPropertyInteger("presenceInstanceID") != 0)
            $this->RegisterTrigger("PresenceTrigger", $this->ReadPropertyInteger("presenceInstanceID"), 'BH_UpdatePresence($_IPS[\'TARGET\']);');

        $this->UpdatePresence();
        $this->UpdateWindow();
        $this->UpdateHeatingMode();
        if($this->ReadPropertyInteger("boostInstanceID") != 0)
            $this->UpdateBoost();

	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "Boost":
                if($Value == false)
                    $this->DeactivateBoost();
                else
                    $this->IncreaseBoost();

                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    private function DeactivateBoost()
    {
        $boostId = $this->GetIDForIdent("Boost");

        SetValue($boostId, false);
        $this->SetValueForIdent("BoostTime", 0);
        IPS_SetName($boostId, "Boost");
        EIB_Switch(IPS_GetParent($this->ReadPropertyInteger("boostInstanceID")), false);
        $this->RegisterTimer("UpdateBoost", 0, 'BH_UpdateBoost($_IPS[\'TARGET\']);'); 
    }

    private function IncreaseBoost()
    {
        $boostId = $this->GetIDForIdent("Boost");
        $boostTime = $this->GetValueForIdent("BoostTime");

        $boostIncrease = 30;
        $boostIncreaseMax = 60;

        $boostTime += $boostIncrease;
        $boostTime = min($boostTime, $boostIncreaseMax);
        $boostTime = max($boostTime, $boostIncrease);

        SetValue($boostId, true);
        IPS_SetName($boostId, "Boost ($boostTime Minuten)");
        $this->RegisterTimer("UpdateBoost", 60, 'BH_UpdateBoost($_IPS[\'TARGET\']);'); 
        $this->SetValueForIdent("BoostTime", $boostTime);
        EIB_Switch(IPS_GetParent($this->ReadPropertyInteger("boostInstanceID")), $Value);
    }

}
?>