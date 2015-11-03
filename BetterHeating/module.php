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
		

        $link = IPS_CreateLink();
        IPS_SetName($link, "currentTempLink");
        IPS_SetIdent($link, "currentTempLink");
        IPS_SetParent($link, $this->InstanceID);
        IPS_SetLinkTargetID($link, $this->ReadPropertyInteger("currentTempInstanceID"));
	}

    public function Update() {
        IPS_LogMessage("BetterHeating", "Update");

        // Create links.
        $link = @IPS_GetObjectIDByIdent("currentTempLink", $this->InstanceID);
        IPS_LogMessage("BetterHeating", "InstanceID: ". $this->InstanceID. " link: ". $link);
        if($link !== false)
        {
            IPS_LogMessage("BetterHeating", "found link");
            IPS_DeleteLink($link);
        }
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