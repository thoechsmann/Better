<?
class BetterHeating extends IPSModule {
		
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
	}
	
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
        // Create links.
        while(IPS_GetInstanceIDByName("currentTempLink", $this->InstanceID) !== false)
        {
            IPS_DeleteLink(IPS_GetInstanceIDByName("currentTempLink", $this->InstanceID));
        }

        $link = IPS_CreateLink();
        IPS_SetName($link, "currentTempLink");
        IPS_SetParent($link, $this->InstanceID);
        IPS_SetLinkTargetID($link, $this->ReadPropertyInteger("currentTempInstanceID"));



		// $this->RegisterVariableFloat("CurrentTemp", "Current Temperature");
  //       $this->RegisterVariableFloat("TargetTemp", "Target Temperature");
		// $this->RegisterVariableString("Holiday", "Holiday");
		//$this->RegisterEventCyclic("UpdateTimer", "Automatische aktualisierung", 15);
	}

    public function Update() {
  //       $holiday = $this->GetFeiertag();

  //       IPS_SetHidden($this->GetIDForIdent("IsHoliday"),true);

		// SetValue($this->GetIDForIdent("Holiday"), $holiday);

  //       if($holiday != "Arbeitstag" and $holiday != "Wochenende") {
  //           SetValue($this->GetIDForIdent("IsHoliday"), true);
  //       }
  //       else {
  //           SetValue($this->GetIDForIdent("IsHoliday"), false);
  //       }
    }        
}
?>