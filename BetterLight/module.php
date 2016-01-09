<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;
    private $sceneCount = 3;

    // getters
    private function SceneName($i)
    {
        return $this->ReadPropertyString("scene".$i."Name");
    }

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("masterMS_MainSwitchId", 0);
        $this->RegisterPropertyInteger("light1_SwitchId", 0);
        $this->RegisterPropertyInteger("light2_SwitchId", 0);

        for($i = 1; $i <= $sceneCount; $i++)
            $this->RegisterPropertyString($this->SceneName($i), "");
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RemoveAll();
        $this->CreateMotionTrigger();
        $this->CreateScenes();
        $this->CreateSceneProfile();
	}

    private function CreateMotionTrigger()
    {
        $this->RegisterTrigger("MSMainSwitchTrigger", $this->ReadPropertyInteger("masterMS_MainSwitchId"), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);
    }

    private function CreateScenes()
    {
        $this->CreateSceneVars("default");

        for($i = 0; $i < $sceneCount; $i++)
            $this->CreateSceneVars($this->SceneName($i));
    }

    private function CreateSceneVars($sceneName)
    {
        if($sceneName === "")
            return;

        IPS_LogMessage("BetterLight", "CreateSceneVars " . $sceneName);

        $this->RegisterVariableBoolean("Light1Value_" . $sceneName, "Licht1 (" . $sceneName . ")", "~Switch");
        $this->RegisterVariableBoolean("Light2Value_" . $sceneName, "Licht2 (" . $sceneName . ")", "~Switch");
    }

    private function CreateSceneProfile()
    {
        $profileName = "BL_scenes_" . $this->GetName();
        IPS_LogMessage("BetterLight", "CreateSceneProfile " . $profileName);

        IPS_DeleteVariableProfile($profileName);
        IPS_CreateVariableProfile($profileName, 1);

        //Anlegen für Wert 1 in der Farbe weiß
        IPS_SetVariableProfileAssociation($profileName, 0, "Default");

        for($i = 1; $i <= $sceneCount; $i++)
        {
            if($this->SceneName($i) !== "")
                IPS_SetVariableProfileAssociation($profileName, $i, $this->SceneName($i));
        }
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