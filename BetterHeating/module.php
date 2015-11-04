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
		
        $this->AddLink("Aktuelle Temperatur", "CurrentTemp", $this->ReadPropertyInteger("currentTempInstanceID"));
        $this->AddLink("Aktuelle Soll Temperatur", "CurrentTargetTemp", $this->ReadPropertyInteger("currentTargetTempInstanceID"));
        $this->AddLink("Stellwert", "ControlValue", $this->ReadPropertyInteger("controlValueInstanceID"));
        $this->AddLink("Soll Temperatur (Komfort)", "TargetComfortTemp", $this->ReadPropertyInteger("targetTempComfortInstanceID"));
        $this->AddLink("Modus", "Mode", $this->ReadPropertyInteger("modeInstanceID"));
	}

    public function Update() {
        IPS_LogMessage("BetterHeating", "Update");
    }       

    private function AddLink($name, $ident, $targetInstanceID) 
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
    }
}
?>