<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;
    private $maxLights = 3;
    private $maxScenes = 4;

    private $idendStr_currentScene = "CurrentScene";
    private $str_light = "light";
    private $str_scene = "scene";

    private function LightSwitchIDString($i)
    {
        return $this->str_light . ($i+1) ."_SwitchId";
    }

    private function LightSwitchID($i)
    {
        return @$this->ReadPropertyInteger($this->LightSwitchIDString($i));
    }

    private function LightDimIDString($i)
    {
        return $this->str_light . ($i+1) ."_DimId";
    }

    private function LightDimID($i)
    {
        return @$this->ReadPropertyInteger($this->LightDimIDString($i));
    }

    private function LightVar($lightNumber, $sceneNumber)
    {
        return $this->str_light . $lightNumber . $this->str_scene . $sceneNumber;
    }

    private function LightNumberForLightVar($lightVar)
    {
        $prefix = substr($lightVar, 0 , strlen($this->str_light));

        if($prefix !== $this->str_light)
            return false;

        $lightNumber = substr($lightVar, strlen($prefix) , 1);

        if(!is_numeric($lightNumber))
            return false;

        return $lightNumber;
    }

    private function SceneNumberForLightVar($lightVar)
    {
        $substr = substr($lightVar, strlen($this->str_light) + 1 , strlen($this->str_scene));

        if($substr !== $this->str_scene)
            return false;

        $sceneNumber = substr($lightVar, strlen($this->str_light) + 1 + strlen($this->str_scene) , 1);

        if(!is_numeric($sceneNumber))
            return false;

        return $sceneNumber;
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

    private function CurrentSceneNumber()
    {
        return $this->GetValueForIdent($this->idendStr_currentScene);
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
        $this->UseCurrentSceneVars();
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
            return;
        }

        $ident = $this->LightVar($lightNumber, $sceneNumber);
        $sceneName = $this->SceneName($sceneNumber);
        $dimId = $this->LightDimID($lightNumber);

        if($dimId === 0)
        {
            $this->RegisterVariableBoolean($ident, "Licht" . ($lightNumber + 1) ." (" . $sceneName . ")", "~Switch");
        }
        else
        {
            $this->RegisterVariableInteger($ident, "Licht" . ($lightNumber + 1) ." (" . $sceneName . ")", "~Intensity.100");
        }

        $this->EnableAction($ident);
    }

    private function CreateSceneProfile()
    {        
        @IPS_DeleteVariableProfile($this->ProfileString());
        IPS_CreateVariableProfile($this->ProfileString(), 1);
        
        for($i = 0; $i < $this->maxScenes; $i++)
        {
            if($this->SceneName($i) !== "")
                IPS_SetVariableProfileAssociation($this->ProfileString(), $i, $this->SceneName($i), "", 0xFFFFFF);
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $ident = $this->idendStr_currentScene;

        $this->RegisterVariableInteger($ident, "Szene", $this->ProfileString());
        $this->EnableAction($ident);
    }

    private function UseCurrentSceneVars()
    {
        for($sceneNumber = 0; $sceneNumber < $this->maxScenes; $sceneNumber++)
        {
            for($lightNumber = 0; $lightNumber < $this->maxLights; $lightNumber++)
            {
                $ident = $this->LightVar($lightNumber, $sceneNumber);
                $id = @$this->GetIDForIdent($ident);

                if($id != 0)
                {
                    $isCurrentScene = ($sceneNumber != $this->CurrentSceneNumber());
                    
                    IPS_SetHidden($id, !$isCurrentScene);

                    if($isCurrentScene)
                    {
                        $switchId = @$this->LightSwitchID($lightNumber);
                        $dimId = @$this->LightDimID($lightNumber);

                        if($dimId == 0)
                        {
                            // No dim id, so it is a switch.
                            EIB_Switch($switchId->parent, $this->GetValueForIdent($ident));
                        }
                        else
                        {
                            EIB_Switch($dimId->parent, $this->GetValueForIdent($ident));
                        }
                    }
                }
            }
        }
    }

    public function RequestAction($Ident, $Value) 
    {
        $lightNumber = $this->LightNumberForLightVar($Ident);
        $sceneNumber = $this->SceneNumberForLightVar($Ident);
        
        if($lightNumber !== false && $sceneNumber !== false)
        {
            // $lightID = $this->LightSwitchID($lightNumber);
            // $this->SetValueForIdent($Ident, $Value);

            // if($sceneNumber == $this->CurrentSceneNumber())
            //     EIB_Switch(IPS_GetParent($lightID), $Value);
            $this->SetValueForIdent($Ident, $Value);
            $this->UseCurrentSceneVars();
        }
        else
        {
            switch($Ident) {
                case $this->idendStr_currentScene:
                    $this->SetValueForIdent($Ident, $Value);
                    $this->UseCurrentSceneVars();
                    break;

                default:
                    $this->SetValueForIdent($Ident, $Value);
                    throw new Exception("Invalid Ident");
            }
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