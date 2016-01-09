<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;
    private $maxLights = 3;
    private $maxScenes = 4;

    private $idendStr_currentScene = "CurrentScene";

    private function LightSwitchIDString($i)
    {
        return "light" . ($i+1) ."_SwitchId";
    }

    private function LightSwitchID($i)
    {
        return $this->ReadPropertyInteger($this->LightSwitchIDString($i));
    }

    private function LightDimIDString($i)
    {
        return "light" . ($i+1) ."_DimId";
    }

    private function LightDimID($i)
    {
        return $this->ReadPropertyInteger($this->LightDimIDString($i));
    }

    private function LightVar($lightNumber, $sceneNumber)
    {
        $sceneName = $this->SceneName($sceneNumber);
        return "Light" . $lightNumber . $sceneName;
    }

    private function SceneName($i)
    {
        if($i === 0)
            return "default";

        return $this->ReadPropertyString($this->SceneString($i));
    }

    private function SceneString($i)
    {
        return "scene". $i ."Name";
    }

    private function SceneCount()
    {
        $count = 0;

        for($i = 0; $i < $this->maxScenes; $i++)
        {
            if($this->SceneName($i) !== "")
                $count++;
        }   

        return $count;
    }

    private function CurrentScene()
    {
        $this->GetValueForIdent($idendStr_currentScene);
    }

    private function ProfileString()
    {
        return "BL_scenes_" . $this->GetName(). $this->InstanceID;
    }

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger("masterMS_MainSwitchId", 0);
        
        for($i = 0; $i < $this->maxLights; $i++)
        {
            $this->RegisterPropertyInteger($this->LightSwitchIDString($i), 0);
            $this->RegisterPropertyInteger($this->LightDimIDString($i), 0);
        }

        for($i = 1; $i < $this->maxScenes; $i++)
        {
            $this->RegisterPropertyString($this->SceneString($i), "");
        }
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
        for($i = 0; $i < $this->maxScenes; $i++)
        {
            $this->CreateSceneVars($i);
        }
    }

    private function CreateSceneVars($sceneNumber)
    {
        $sceneName = $this->SceneName($sceneNumber);
        
        if($sceneName === "")
            return;

        for($i=0; $i<$this->maxLights; $i++)
        {
            $this->CreateLight($sceneNumber, $i);
        }
    }

    private function CreateLight($sceneNumber, $lightNumber)
    {
        $switchId = $this->LightSwitchID($lightNumber);

        if($switchId === 0)
        {
            continue;
        }

        $ident = $this->LightVar($lightNumber, $sceneNumber);
        $sceneName = $this->SceneName($sceneNumber);
        $dimId = $this->LightDimID($lightNumber);

        if($dimId === 0)
        {
            $this->RegisterVariableBoolean($ident, "Licht1 (" . $sceneName . ")", "~Switch");
        }
        else
        {
            $this->RegisterVariableInteger($ident, "Licht1 (" . $sceneName . ")", "~Intensity.100");
        }
    }

    private function CreateSceneProfile()
    {        
        @IPS_DeleteVariableProfile($this->ProfileString());
        IPS_CreateVariableProfile($this->ProfileString(), 1);
        
        IPS_SetVariableProfileAssociation($this->ProfileString(), -1, "Default", "", 0xFFFFFF);

        for($i = 0; $i < $this->maxScenes; $i++)
        {
            if($this->SceneName($i) !== "")
                IPS_SetVariableProfileAssociation($this->ProfileString(), $i, $this->SceneName($i), "Speaker", 0xFFFFFF);
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $this->RegisterVariableInteger($this->idendStr_currentScene, "Szene", $this->ProfileString());
    }

    private function ShowCurrentSceneVars()
    {
        // CurrentScene();

        // IPS_SetHidden($this->GetIDForIdent("Light1Value_" . $sceneName), true);

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