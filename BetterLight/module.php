<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;

    // getters
    private function Scene1Name()
    {
        return $this->ReadPropertyString("scene1Name");
    }

    private function Scene2Name()
    {
        return $this->ReadPropertyString("scene2Name");
    }

    private function Scene3Name()
    {
        return $this->ReadPropertyString("scene3Name");
    }

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("masterMS_MainSwitchId", 0);
        $this->RegisterPropertyInteger("light1_SwitchId", 0);
        $this->RegisterPropertyInteger("light2_SwitchId", 0);

        $this->RegisterPropertyString("scene1Name", "");
        $this->RegisterPropertyString("scene2Name", "");
        $this->RegisterPropertyString("scene3Name", "");
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RemoveAll();

        $this->RegisterTrigger("MSMainSwitchTrigger", $this->ReadPropertyInteger("masterMS_MainSwitchId"), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);

        // Scene settings for each light
        CreateSceneVars("default");

        if(Scene1Name() !== "")
        {
            CreateSceneVars(Scene1Name());
        }
        if(Scene2Name() !== "")
        {
            CreateSceneVars(Scene2Name());
        }
        if(Scene3Name() !== "")
        {
            CreateSceneVars(Scene3Name());
        }

	}

    private function CreateSceneVars($sceneName)
    {
        $this->RegisterVariableBoolean("Light1Value_" + $sceneName, "Licht1 ("+ $sceneName + ")", "~Switch");
        $this->RegisterVariableBoolean("Light2Value_" + $sceneName, "Licht2 ("+ $sceneName + ")", "~Switch");
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
        $lightTwoId = $this->ReadPropertyInteger("light2_SwitchId");
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