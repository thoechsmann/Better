<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");

class BetterLight extends BetterBase {

    // private $isDayId = 18987;
    private $isDayId = 52946;
    private $maxLights = 8;
    private $maxScenes = 4;
    private $maxSwitches = 4;

    private $idendStr_currentScene = "CurrentScene";
    private $str_light = "light";
    private $str_scene = "scene";
    const StrSwitch = "switch";

    const PosSceneSelection = 1;
    const PosMSDisabled = 1;
    const PosLightDim = 2;
    const PosLightSwitch = 3;

    // Properties
    private function MSMainSwitchIdProperty()
    {
        return new PropertyInteger($this, "msMainSwitchId");
    }

    private function MSDeactivateIdProperty()
    {
        return new PropertyInteger($this, "msDeactivateId");
    }

    private function MSExternMovementIdProperty()
    {
        return new PropertyInteger($this, "msExternMovementId");
    }

    private function LightNamePropertyArray()
    {        
        return new PropertyArrayString($this, "Name", $this->str_light, $this->maxLights);
    }

    private function LightSwitchIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "SwitchId", $this->str_light, $this->maxLights);
    }

    private function LightDimIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "DimId", $this->str_light, $this->maxLights);
    }

    private function SceneNamePropertyArray()
    {        
        return new PropertyArrayString($this, "Name", $this->str_scene, $this->maxScenes);
    }

    private function SwitchIdPropertyArray()
    {        
        return new PropertyArrayString($this, "Id", self::StrSwitch, $this->maxSwitches);
    }

    private function SwitchScenePropertyArray()
    {        
        return new PropertyArrayString($this, "Scene", self::StrSwitch, $this->maxSwitches);
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

    private function IsMSDeactivateIdent($ident)
    {
        $start = strlen($this->PERSISTENT_IDENT_PREFIX) + strlen("scene") + 1;
        $string = substr($ident, $start, strlen("MSDeactivate"));

        return $string === "MSDeactivate";
    }

    private function LightNumberForLightIdent($lightIdent)
    {
        $startLen = strlen($this->PERSISTENT_IDENT_PREFIX);
        $string = substr($lightIdent, $startLen , strlen($this->str_light));

        if($string !== $this->str_light)
            return false;

        $startLen += strlen($string);
        $lightNumber = substr($lightIdent, $startLen , 1);

        if(!is_numeric($lightNumber))
            return false;

        return $lightNumber;
    }

    private function SceneNumberForLightIdent($lightIdent)
    {
        $startLen = strlen($this->PERSISTENT_IDENT_PREFIX) + strlen($this->str_light) + 1;
        $string = substr($lightIdent, $startLen , strlen($this->str_scene));

        if($string !== $this->str_scene)
            return false;

        $startLen += strlen($string);
        $sceneNumber = substr($lightIdent, $startLen , 1);

        if(!is_numeric($sceneNumber))
            return false;

        return $sceneNumber;
    }

    private function IsLightIdent($ident)
    {
        $lightNumber = $this->LightNumberForLightIdent($ident);
        $sceneNumber = $this->SceneNumberForLightIdent($ident);
        
        return $lightNumber !== false && $sceneNumber !== false;
    }

    private function SceneCount()
    {
        $count = 0;

        for($i = 0; $i < $this->maxScenes; $i++)
        {
            if($this->SceneNamePropertyArray()->ValueAt($i) !== "")
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
        return "BL_scenes_" . $this->GetName() . $this->InstanceID;
    }

    private function SetLight($lightNumber, $value)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        if($dimId !== 0 && $value > 0)
        {
            EIB_Scale(IPS_GetParent($dimId), $value);
        }
        else if($switchId !== 0)
        {
            EIB_Switch(IPS_GetParent($switchId), $value);
        }
    }

    private function SetMSDeactivate($value)
    {
        $msId = $this->MSDeactivateIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), $value);
        }
    }

    private function SetMSExternMovement()
    {
        $msId = $this->MSExternMovementIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), true);
        }
    }


    //
    //
    //

	public function Create() 
    {
		parent::Create();		
        
        $this->MSMainSwitchIdProperty()->Register();
        $this->MSDeactivateIdProperty()->Register();
        $this->MSExternMovementIdProperty()->Register();
        $this->LightNamePropertyArray()->RegisterAll();
        $this->LightSwitchIdPropertyArray()->RegisterAll();
        $this->LightDimIdPropertyArray()->RegisterAll();
        $this->SceneNamePropertyArray()->RegisterAll();
        $this->SwitchIdPropertyArray()->RegisterAll();
        $this->SwitchScenePropertyArray()->RegisterAll();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->CreateMotionTrigger();
        $this->CreateScenes();
        $this->CreateSceneProfile();
        $this->CreateSceneSelectionVar();
        $this->UseCurrentSceneVars();
	}

    private function CreateMotionTrigger()
    {
        $this->RegisterTrigger("MSMainSwitchTrigger", $this->MSMainSwitchIdProperty()->Value(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);
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
        $sceneName = $this->SceneNamePropertyArray()->ValueAt($sceneNumber);
        
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
        $ident = $this->LightIdent($lightNumber, $sceneNumber);
        $sceneName = $this->SceneNamePropertyArray()->ValueAt($sceneNumber);

        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        $name = $this->LightNamePropertyArray()->ValueAt($lightNumber);
        $exists = $switchId !== 0;
        $profile = "~Switch";
        $type = 0;
        $pos = self::PosLightSwitch;

        if($dimId !== 0)
        {
            if($switchId == 0)
                throw new Exception("DimId without switch id for light number " . $lightNumber. "!");

            $profile = "~Intensity.100";
            $type = 1;
            $pos = self::PosLightDim;
        }

        $this->MaintainVariable($ident, $name, $type, $profile, 0, $exists);

        if($exists)
        {
            $this->EnableAction($ident);
            $id = $this->GetIDForIdent($ident);
            IPS_SetName($id, $name);
            IPS_SetPosition($id, $pos);
        }
    }

    private function CreateMSDeactivate($sceneNumber)
    {
        $ident = $this->MSDeactivateIdent($sceneNumber);
        $sceneName = $this->SceneNamePropertyArray()->ValueAt($sceneNumber);
        $this->RegisterVariableBoolean($ident, "BM Sperren (" . $sceneName . ")", "~Switch");
        $this->EnableAction($ident);
    }

    private function CreateSceneProfile()
    {        
        @IPS_DeleteVariableProfile($this->ProfileString());
        IPS_CreateVariableProfile($this->ProfileString(), 1);
        
        for($i = 0; $i < $this->maxScenes; $i++)
        {
            if($this->SceneNamePropertyArray()->ValueAt($i) !== "")
                IPS_SetVariableProfileAssociation($this->ProfileString(), $i, $this->SceneNamePropertyArray()->ValueAt($i), "", 0xFFFFFF);
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
        $this->SetMSExternMovement();

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

    public function RequestAction($ident, $value) 
    {
        if($this->IsLightIdent($ident))
        {
            $this->SetValueForIdent($ident, $value);
            $lightNumber = $this->LightNumberForLightIdent($ident);
            $this->SetLight($lightNumber, $value);
        }
        else if($this->IsMSDeactivateIdent($ident))
        {
            $this->SetValueForIdent($ident, $value);
            $this->SetMSDeactivate($value);
        }
        else
        {
            switch($ident) {
                case $this->idendStr_currentScene:
                    $this->SetValueForIdent($ident, $value);
                    $this->UseCurrentSceneVars();
                    break;

                default:
                    $this->SetValueForIdent($ident, $value);
                    throw new Exception("Invalid Ident: " . $ident);
            }
        }
    }

    public function MSMainSwitchEvent()
    {
        $msId = $this->MSMainSwitchIdProperty()->Value();
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
            $this->SetLight($lightNumber, 0);
        }
    }

}
?>