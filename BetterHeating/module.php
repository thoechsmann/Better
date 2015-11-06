<?
class BetterHeating extends IPSModule {

	public function Update() 
    {
        // IPS_LogMessage("BetterHeating", "update");

        // Check window State
        $maxWindows = 7;
        $windowOpenId = IPS_GetObjectIDByIdent("WindowOpen", $this->InstanceID);
        $openWindowCount = 0;

        for($i = 1; $i <= $maxWindows; $i++)
        {
            $id = $this->ReadPropertyInteger("window" . $i . "InstanceID");
            if($id!=0 && GetValue($id) === true)
            {
                $openWindowCount++;
            }
        }

        IPS_SetHidden($windowOpenId, $openWindowCount == 0);

        // Check Heating Mode
        $modeId = $this->ReadPropertyInteger("modeInstanceID");
        $mode = GetValue($modeId);
        $CurrentTargetTempId = IPS_GetObjectIDByIdent("CurrentTargetTemp", $this->InstanceID);
        $TargetComfortTempId = IPS_GetObjectIDByIdent("TargetComfortTemp", $this->InstanceID);

        IPS_SetHidden($CurrentTargetTempId, $mode == 1);        
        IPS_SetHidden($TargetComfortTempId, $mode != 1);        
    }

	public function Create() 
    {
		//Never delete this line!
		parent::Create();		

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
        $this->RegisterPropertyInteger("currentTempInstanceID", 0);
        $this->RegisterPropertyInteger("currentTargetTempInstanceID", 0);
        $this->RegisterPropertyInteger("controlValueInstanceID", 0);
        $this->RegisterPropertyInteger("targetTempComfortInstanceID", 0);
        $this->RegisterPropertyInteger("modeInstanceID", 0);

        $this->RegisterPropertyInteger("window1InstanceID", 0);
        $this->RegisterPropertyInteger("window2InstanceID", 0);
        $this->RegisterPropertyInteger("window3InstanceID", 0);
        $this->RegisterPropertyInteger("window4InstanceID", 0);
        $this->RegisterPropertyInteger("window5InstanceID", 0);
        $this->RegisterPropertyInteger("window6InstanceID", 0);
        $this->RegisterPropertyInteger("window7InstanceID", 0);
	}
	
	public function ApplyChanges() 
    {
		//Never delete this line!
		parent::ApplyChanges();
		
        $this->RegisterLink("CurrentTemp", "Temperatur", $this->ReadPropertyInteger("currentTempInstanceID"), 1);
        $this->RegisterLink("CurrentTargetTemp", "Soll Temperatur", $this->ReadPropertyInteger("currentTargetTempInstanceID"), 2);
        $this->RegisterLink("TargetComfortTemp", "Soll Temperatur (Komfort)", $this->ReadPropertyInteger("targetTempComfortInstanceID"), 3);
        $this->RegisterLink("Mode", "Modus", $this->ReadPropertyInteger("modeInstanceID"), 4);
        $this->RegisterLink("ControlValue", "Stellwert", $this->ReadPropertyInteger("controlValueInstanceID"), 5);

        $this->RegisterVariableString("WindowOpen", "Fenster ist geöffnet -> Heizung aus");

        $profileName = "BH_Boost";
        if (@IPS_GetVariableProfile($profileName) === false && IPS_CreateVariableProfile($profileName, 1)) 
        { 
            IPS_SetVariableProfileDigits($profileName, 1); 
            IPS_SetVariableProfileAssociation($profileName, 1, 'AN (%d)', '', -1); 
            IPS_SetVariableProfileAssociation($profileName, 0, 'AUS', '', -1); 
        }
        
        $id = $this->RegisterVariableInteger("Boost", "Boost");
        $this->EnableAction("Boost");
        IPS_SetVariableCustomProfile($id, $profileName);

        $id = $this->RegisterVariableInteger("BoostTime", "BoostTime");
        IPS_SetHidden($id, true);
        $id = $this->RegisterVariableInteger("BoostStartTime", "BoostStartTime");
        IPS_SetHidden($id, true);

        $this->RegisterTimer("CheckWindows", 1, 'BH_Update($_IPS[\'TARGET\']);');
	}

    public function RequestAction($Ident, $Value) 
    {    
        IPS_LogMessage("BetterHeating", "RequestAction for $Ident");

        switch($Ident) {
            case "Boost":
                $boostTimeId = $this->GetIDForIdent("BoostTime");
                $boostTime = GetValue($boostTimeId);

                if($Value == 0)
                {
                    $boostTime = 0;
                }
                else
                {
                    $boostTime += 30;
                }

                SetValue($boostTimeId, $boostTime);
                SetValue($this->GetIDForIdent($ident), $boostTime);

                break;
            default:
                throw new Exception("Invalid Ident");
        }

        IPS_LogMessage("BetterHeating", "RequestAction for $Ident done");
    }

    private function RegisterLink($ident, $name, $targetInstanceID, $position) 
    {
        $link = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if($link !== false)
        {
            IPS_DeleteLink($link);
        }

        $link = IPS_CreateLink();
        IPS_SetName($link, $name);
        IPS_SetIdent($link, $ident);
        IPS_SetParent($link, $this->InstanceID);
        IPS_SetLinkTargetID($link, $targetInstanceID);
        IPS_SetPosition($link, $position);

        return $link;
    }

    private function RegisterTimer($ident, $interval, $script) 
    { 
        $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID); 

        if ($id && IPS_GetEvent($id)['EventType'] <> 1) { 
            IPS_DeleteEvent($id); 
            $id = 0; 
        } 

        if (!$id) { 
            $id = IPS_CreateEvent(1); 
            IPS_SetParent($id, $this->InstanceID); 
            IPS_SetIdent($id, $ident);  
        } 

        IPS_SetName($id, $ident); 
        IPS_SetHidden($id, true); 
        IPS_SetEventScript($id, "$script;"); 

        if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type"); 

        if ($interval > 0) { 
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval); 
            IPS_SetEventActive($id, true); 
        } else { 
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1); 
            IPS_SetEventActive($id, false); 
        } 
    }
}
?>