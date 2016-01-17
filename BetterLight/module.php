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

    // Property strings

    private $MSMainSwitchIdPropertyName = "ms_MainSwitchId";
    private $MSDeactivateIdPropertyName = "ms_DeactivateId";

    private function LightSwitchIdString($lightNumber)
    {
        return $this->str_light . ($lightNumber+1) ."_SwitchId";
    }

    private function LightDimIdString($lightNumber)
    {
        return $this->str_light . ($lightNumber+1) ."_DimId";
    }

    private function SceneString($sceneNumber)
    {
        return "scene". $sceneNumber ."Name";
    }

    // property values

    private function LightSwitchId($lightNumber)
    {
        return @$this->ReadPropertyInteger($this->LightSwitchIdString($lightNumber));
    }

    private function LightDimId($lightNumber)
    {
        return @$this->ReadPropertyInteger($this->LightDimIdString($lightNumber));
    }

    private function SceneName($sceneNumber)
    {
        if($sceneNumber === 0)
            return "default";

        return $this->ReadPropertyString($this->SceneString($sceneNumber));
    }

    private function MSMainSwitchId()
    {
        return $this->ReadPropertyString($this->MSMainSwitchIdPropertyName);
    }

    private function MSDeactivateId()
    {
        return $this->ReadPropertyString($this->MSDeactivateIdPropertyName);
    }

    // 

    private function LightIdent($lightNumber, $sceneNumber)
    {
        return $this->PERSISTENT_IDENT_PREFIX . $this->str_light . $lightNumber . $this->str_scene . $sceneNumber;
    }

    private function MSDeactivateIdent($sceneNumber)
    {
        return $this->PERSISTENT_IDENT_PREFIX . "scene". $sceneNumber ."MSDeactivate";
    }

    private function LightNumberForLightIdent($lightIdent)
    {
        $string = substr($lightIdent, $this->PERSISTENT_IDENT_PREFIX , strlen($this->str_light));

        if($string !== $this->str_light)
            return false;

        $lightNumber = substr($lightIdent, strlen($string) + strlen($this->PERSISTENT_IDENT_PREFIX) , 1);

        if(!is_numeric($lightNumber))
            return false;

        return $lightNumber;
    }

    private function SceneNumberForLightIdent($lightIdent)
    {
        $string = substr($lightIdent, strlen($this->PERSISTENT_IDENT_PREFIX) + strlen($this->str_light) + 1 , strlen($this->str_scene));

        if($string !== $this->str_scene)
            return false;

        $sceneNumber = substr($lightIdent, strlen($this->PERSISTENT_IDENT_PREFIX) + strlen($this->str_light) + 1 + strlen($this->str_scene) , 1);

        if(!is_numeric($sceneNumber))
            return false;

        return $sceneNumber;
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

    private function SetLight($lightNumber, $value)
    {
        $switchId = @$this->LightSwitchId($lightNumber);
        $dimId = @$this->LightDimId($lightNumber);

        if($dimId == 0)
        {
            EIB_Switch(IPS_GetParent($switchId), $value);
        }
        else
        {
            EIB_Scale(IPS_GetParent($dimId), $value);
        }
    }

    private function SetMSDeactivate($value)
    {
        $id = @$this->MSDeactivateId();;

        EIB_Switch(IPS_GetParent($id), $value);
    }

    //
    //
    //

	public function Create() 
    {
		parent::Create();		

        $this->RegisterPropertyInteger($this->MSMainSwitchIdPropertyName, 0);
        $this->RegisterPropertyInteger($this->MSDeactivateIdPropertyName, 0);
        
        for($i = 0; $i < $this->maxLights; $i++)
        {
            $this->RegisterPropertyInteger($this->LightSwitchIdString($i), 0);
            $this->RegisterPropertyInteger($this->LightDimIdString($i), 0);
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
        $this->RegisterTrigger("MSMainSwitchTrigger", $this->MSMainSwitchId(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);
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

        $this->CreateMSDeactivate($sceneNumber);
    }

    private function CreateLight($sceneNumber, $lightNumber)
    {
        $switchId = $this->LightSwitchId($lightNumber);

        if($switchId === 0)
        {
            return;
        }

        $ident = $this->LightIdent($lightNumber, $sceneNumber);
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

    private function CreateMSDeactivate($sceneNumber)
    {
        $ident = $this->MSDeactivateIdent($sceneNumber);
        $sceneName = $this->SceneName($sceneNumber);
        $this->RegisterVariableBoolean($ident, "MS Sperren (" . $sceneName . ")", "~Switch");
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
        $currentSceneNumber = $this->CurrentSceneNumber();

        for($sceneNumber = 0; $sceneNumber < $this->maxScenes; $sceneNumber++)
        {
            $isCurrentScene = ($sceneNumber == $currentSceneNumber);

            $msIdent = $this->MSDeactivateIdent($sceneNumber);
            $msId = @$this->GetIDForIdent($msIdent);

            if($msId !== false)
            {                    
                IPS_SetHidden($msId, !$isCurrentScene);

                if($isCurrentScene)
                {
                    $this->SetMSDeactivate($this->GetValueForIdent($msIdent));
                }
            }

            for($lightNumber = 0; $lightNumber < $this->maxLights; $lightNumber++)
            {
                $ident = $this->LightIdent($lightNumber, $sceneNumber);
                $id = @$this->GetIDForIdent($ident);

                if($id !== false)
                {                    
                    IPS_SetHidden($id, !$isCurrentScene);

                    if($isCurrentScene)
                    {
                        $this->SetLight($lightNumber, $this->GetValueForIdent($ident));
                    }
                }
            }            
        }
    }

    public function RequestAction($Ident, $Value) 
    {
        $lightNumber = $this->LightNumberForLightIdent($Ident);
        $sceneNumber = $this->SceneNumberForLightIdent($Ident);
        
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
                    throw new Exception("Invalid Ident: " . $ident);
            }
        }
    }

    public function MSMainSwitchEvent()
    {
        IPS_LogMessage("BetterLight", "BSMainSwitchEvent");
        $msId = $this->MSMainSwitchId();
        $turnOn = GetValue($msId);

        if($turnOn)
        {
            $this->UseCurrentSceneVars();
        }
        else
        {
            $this->TurnOffAll();
        }
    }

    public function TurnOffAll()
    {
        for($lightNumber = 0; $lightNumber < $this->maxLights; $lightNumber++)
        {
            $ident = $this->LightIdent($lightNumber, $sceneNumber);
            $id = @$this->GetIDForIdent($ident);

            if($id != 0)
            {
                $this->SetLight($ident, 0);
            }   
        }
    }

}
?>