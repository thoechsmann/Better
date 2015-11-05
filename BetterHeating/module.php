<?
class BetterHeating extends IPSModule {
	public function Update() {
        IPS_LogMessage("BetterHeating", "update");

        $maxWindows = 7;

        for($i = 0; $i < $maxWindows; $i++)
        {
            IPS_LogMessage("BetterHeating", "UpdateWindowState");
            $id = $this->ReadPropertyInteger("window" + $i + "InstanceID");
            if(GetValue($id) === true)
            {
                $windowOpenId = IPS_GetObjectIDByIdent("WindowOpen", $this->InstanceID);
                IPS_SetHidden($windowOpenId, false);
                IPS_LogMessage("BetterHeating", "UpdateWindowState: window open");
                break;
            }

            IPS_SetHidden($windowOpenId, true);
            IPS_LogMessage("BetterHeating", "UpdateWindowState: all windows closed");
        }
    }

	public function Create() {
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
	
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
        $this->RegisterLink("CurrentTemp", "Temperatur", $this->ReadPropertyInteger("currentTempInstanceID"), 1);
        $this->RegisterLink("CurrentTargetTemp", "Soll Temperatur", $this->ReadPropertyInteger("currentTargetTempInstanceID"), 2);
        $this->RegisterLink("ControlValue", "Stellwert", $this->ReadPropertyInteger("controlValueInstanceID"), 3);
        $this->RegisterLink("TargetComfortTemp", "Soll Temperatur (Komfort)", $this->ReadPropertyInteger("targetTempComfortInstanceID"), 4);
        $this->RegisterLink("Mode", "Modus", $this->ReadPropertyInteger("modeInstanceID"), 5);

        $this->RegisterVariableString("WindowOpen", "Fenster ist geöffnet -> Heizung aus");

        $this->RegisterTimer("CheckWindows", 2, 'BH_Update($_IPS[\'TARGET\']);');
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