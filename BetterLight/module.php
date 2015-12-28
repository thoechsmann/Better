<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("masterBS_MainSwitchId", 0);
        $this->RegisterPropertyInteger("light1_SwitchId", 0);
        $this->RegisterPropertyInteger("light2_SwitchId", 0);
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RemoveAll();

        $this->RegisterVariableBoolean("LightOne_DayValue", "Licht1 Tag", "~Switch");
        $this->RegisterVariableBoolean("LightTwo_DayValue", "Licht2 Tag", "~Switch");
        $this->RegisterVariableBoolean("LightOne_NightValue", "Licht1 Nacht", "~Switch");
        $this->RegisterVariableBoolean("LightTwo_NightValue", "Licht2 Nacht", "~Switch");

        $this->EnableAction("LightOne_DayValue");
        $this->EnableAction("LightTwo_DayValue");
        $this->EnableAction("LightOne_NightValue");
        $this->EnableAction("LightTwo_NightValue");

        $this->RegisterTrigger("MSMainSwitchTrigger", $this->ReadPropertyInteger("masterMS_MainSwitchId"), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);

	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "LightOne_DayValue":
            case "LightTwo_DayValue":
            case "LightOne_NightValue":
            case "LightTwo_NightValue":
                $this->SetValueForIdent($Ident, $Value);
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function MSMainSwitchEvent()
    {
        IPS_LogMessage("BetterLight", "BSMainSwitchEvent");
        $msId = $this->ReadPropertyInteger("masterMS_MainSwitchId");
        $turnOn = GetValue($msId);

        $lightOneId = $this->ReadPropertyInteger("light1_SwitchId");
        $lightTwoId = $this->ReadPropertyInteger("light1_SwitchId");
        $lightOneDayValue = $this->GetValueForIdent("LightOne_DayValue");
        $lightTwoDayValue = $this->GetValueForIdent("LightTwo_DayValue");


        if($turnOn)
        {
            EIB_Switch(IPS_GetParent($lightOneId), $lightOneDayValue);        
            EIB_Switch(IPS_GetParent($lightTwoId), $lightTwoDayValue);        
        }
        else
        {
            EIB_Switch(IPS_GetParent($lightOneId), false);        
            EIB_Switch(IPS_GetParent($lightTwoId), false);                    
        }
    }

}
?>