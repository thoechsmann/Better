<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;
    private $maxScenes = 3;

    private function SceneName($i)
    {
        return $this->ReadPropertyString(SceneString($i));
    }

    private function SceneString($i)
    {
        return "scene". ($i + 1) ."Name";
    }

    private function SceneCount()
    {
        $count = 0;

        for($i = 0; $i < $maxScenes; $i++)
        {
            if(SceneName($i) !== "")
                $count++;
        }   

        return $count;
    }

    private function ProfileString()
    {
        return "BL_scenes_" . $this->GetName(). $this->InstanceID;
    }

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("masterMS_MainSwitchId", 0);
        $this->RegisterPropertyInteger("light1_SwitchId", 0);
        $this->RegisterPropertyInteger("light2_SwitchId", 0);

        for($i = 0; $i < $maxScenes; $i++)
            $this->RegisterPropertyString($this->SceneString($i), "");
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->RemoveAll();
        $this->CreateMotionTrigger();
        $this->CreateScenes();
        $this->CreateSceneProfile();
        $this->CreateSceneSelectionVar();
	}

    private function CreateMotionTrigger()
    {
        $this->RegisterTrigger("MSMainSwitchTrigger", $this->ReadPropertyInteger("masterMS_MainSwitchId"), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);
    }

    private function CreateScenes()
    {
        $this->CreateSceneVars("default");

        for($i = 0; $i < $maxScenes; $i++)
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
        IPS_LogMessage("BetterLight", "CreateSceneProfile " . $this->ProfileString());

        IPS_DeleteVariableProfile($this->ProfileString());
        IPS_CreateVariableProfile($this->ProfileString(), 1);

        //Anlegen für Wert 1 in der Farbe weiß
        IPS_SetVariableProfileAssociation($this->ProfileString(), 0, "Default");

        for($i = 0; $i < $maxScenes; $i++)
        {
            if($this->SceneName($i) !== "")
                IPS_SetVariableProfileAssociation($this->ProfileString(), $i, $this->SceneName($i));
        }
    }

    public function CreateSceneSelectionVar() 
    {
        $this->RegisterVariableInteger("SceneSelection", "Szene", $this->ProfileString());
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