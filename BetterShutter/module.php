<?
class BetterShutter extends IPSModule {
		
	public function Create() {
		//Never delete this line!
		parent::Create();		
		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		// $this->RegisterPropertyString("area", "NI");		
	}
	
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
		// $this->RegisterVariableFloat("CurrentTemp", "Current Temperature");
        // $this->RegisterVariableFloat("TargetTemp", "Target Temperature");
		// $this->RegisterVariableString("Holiday", "Holiday");
		//$this->RegisterEventCyclic("UpdateTimer", "Automatische aktualisierung", 15);

        // $link = IPS_CreateLink();
        // IPS_SetName($link, "PositionLink");
        // IPS_SetParent($link, $this->InstanceID);
//        IPS_SetLinkTargetID($link, $this->GetIDForIdent("Position"));
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