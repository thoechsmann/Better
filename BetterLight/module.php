<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");

class BetterLight extends BetterBase {

    const MaxLights = 8;
    const MaxScenes = 4;
    const MaxSwitches = 4;

    const StrLight = "light";
    const StrScene = "scene";
    const StrSwitch = "Switch";
    const StrDim = "Dim";
    const StrLink = "Link";

    const PosSceneSelection = 1;
    const PosMSDisabled = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosSaveSceneButton = 5;

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
        return new PropertyArrayString($this, "Name", self::StrLight, self::MaxLights);
    }

    private function LightSwitchIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "SwitchId", self::StrLight, self::MaxLights);
    }

    private function LightDimIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "DimId", self::StrLight, self::MaxLights);
    }

    private function LightStatusSwitchIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "StatusSwitchId", self::StrLight, self::MaxLights);
    }

    private function LightStatusDimIdPropertyArray()
    {        
        return new PropertyArrayInteger($this, "StatusDimId", self::StrLight, self::MaxLights);
    }

    private function SceneNamePropertyArray()
    {        
        return new PropertyArrayString($this, "Name", self::StrScene, self::MaxScenes);
    }

    private function SwitchIdPropertyArray()
    {        
        return new PropertyArrayString($this, "Id", self::StrSwitch, self::MaxSwitches);
    }

    private function SwitchScenePropertyArray()
    {        
        return new PropertyArrayString($this, "Scene", self::StrSwitch, self::MaxSwitches);
    }

    // 

    const SaveSceneIdent = "SaveScene";
    const CurrentSceneIdent = "CurrentScene";
    const SaveToSceneIdent = "SaveToScene";
    const MSMainSwitchIdent = "MSMainSwitch";
    const MSMainSwitchTriggerIdent = "MSMainSwitchTrigger";

    private function LightSwitchIdent($lightNumber)
    {
        return self::StrLight . self::StrSwitch . $lightNumber;
    }

    private function LightDimIdent($lightNumber)
    {
        return self::StrLight . self::StrDim . $lightNumber;
    }

    private function SceneLightSwitchIdent($lightNumber, $sceneNumber)
    {
        return $this->PERSISTENT_IDENT_PREFIX . self::StrLight . $lightNumber . self::StrScene . $sceneNumber . self::StrSwitch;
    }

    private function SceneLightDimIdent($lightNumber, $sceneNumber)
    {
        return $this->PERSISTENT_IDENT_PREFIX . self::StrLight . $lightNumber . self::StrScene . $sceneNumber . self::StrDim;
    }

    private function LightSwitchLinkIdent($lightNumber)
    {
        return self::StrLight . $lightNumber . "Switch";
    }

    private function LightDimLinkIdent($lightNumber)
    {
        return self::StrLight . $lightNumber . "Dim";
    }

    private function MSDeactivateIdent($sceneNumber)
    {
        return $this->PERSISTENT_IDENT_PREFIX . "scene". $sceneNumber ."MSDeactivate";
    }

    private function LightNumberForLightSwitchIdent($lightIdent)
    {
        $lightNumber = substr($lightIdent, strlen(self::StrLight . self::StrSwitch), 1);

        if(!is_numeric($lightNumber))
            return false;

        return $lightNumber;
    }

    private function LightNumberForLightDimIdent($lightIdent)
    {
        $lightNumber = substr($lightIdent, strlen(self::StrLight . self::StrDim), 1);

        if(!is_numeric($lightNumber))
            return false;

        return $lightNumber;
    }

    private function SceneNumberForLightIdent($lightIdent)
    {
        $startLen = strlen($this->PERSISTENT_IDENT_PREFIX) + strlen(self::StrLight) + 1;
        $string = substr($lightIdent, $startLen , strlen(self::StrScene));

        if($string !== self::StrScene)
            return false;

        $startLen += strlen($string);
        $sceneNumber = substr($lightIdent, $startLen , 1);

        if(!is_numeric($sceneNumber))
            return false;

        return $sceneNumber;
    }

    private function SceneCount()
    {
        $count = 0;

        for($i = 0; $i < self::MaxScenes; $i++)
        {
            if($this->SceneNamePropertyArray()->ValueAt($i) !== "")
                $count++;
        }   

        return $count;
    }

    private function CurrentSceneNumber()
    {
        return $this->GetValueForIdent(self::CurrentSceneIdent);
    }

    private function SceneProfileString()
    {
        return "BL_scenes_" . $this->GetName() . $this->InstanceID;
    }

    private function LoadLightFromScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $sceneSwitchIdent = $this->SceneLightSwitchIdent($lightNumber, $sceneNumber);
            $sceneSwitchValue = $this->GetValueForIdent($sceneSwitchIdent);
            $statusSwitchId = $this->LightStatusSwitchIdPropertyArray()->ValueAt($lightNumber);
            $statusSwitchValue = GetValue($statusSwitchId);

            if($sceneSwitchValue != $statusSwitchValue)
            {
                EIB_Switch(IPS_GetParent($switchId), $sceneSwitchValue);
            }
        }
        else
        {
            $sceneDimIdent = $this->SceneLightDimIdent($lightNumber, $sceneNumber);
            $sceneDimValue = $this->GetValueForIdent($sceneDimIdent);
            $statusDimId = $this->LightStatusDimIdPropertyArray()->ValueAt($lightNumber);
            $statusDimValue = GetValue($statusDimId);

            if($sceneDimValue != $statusDimValue)
            {
                EIB_Scale(IPS_GetParent($dimId), $sceneDimValue);
            }
        }
    }

    private function SaveLightToScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $sceneSwitchIdent = $this->SceneLightSwitchIdent($lightNumber, $sceneNumber);
            $statusSwitchId = $this->LightStatusSwitchIdPropertyArray()->ValueAt($lightNumber);
            $statusSwitchValue = GetValue($statusSwitchId);

            $this->SetValueForIdent($sceneSwitchIdent, $statusSwitchValue);
        }
        else
        {
            $sceneDimIdent = $this->SceneLightDimIdent($lightNumber, $sceneNumber);
            $statusDimId = $this->LightStatusDimIdPropertyArray()->ValueAt($lightNumber);
            $statusDimValue = GetValue($statusDimId);

            $this->SetValueForIdent($sceneDimIdent, $statusDimValue);
        }
    }

    private function SetLightSwitch($lightNumber, $value)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);

        if($switchId == 0)
            throw new Exception("Switch id not set, but trying to set it (SetLightSwitch) for light number " . $lightNumber . "!");

        EIB_Switch(IPS_GetParent($switchId), $value);
    }

    private function SetLightDim($lightNumber, $value)
    {
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        if($dimId == 0)
            throw new Exception("Dim id not set, but trying to set it (SetLightDim) for light number " . $lightNumber . "!");

        EIB_Scale(IPS_GetParent($dimId), $value);
    }

    private function SetMSDeactivate($value)
    {
        $msId = $this->MSDeactivateIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), $value);
        }
    }

    private function LoadMSDeactivateFromScene($sceneNumber)
    {
        $msId = $this->MSExternMovementIdProperty()->Value();

        $msSceneIdent = $this->MSDeactivateIdent($sceneNumber);
        $msSceneValue = $this->GetValueForIdent($msSceneIdent);

        EIB_Switch(IPS_GetParent($msId), $msSceneValue);
    }

    private function SaveMSDeactivateToScene($sceneNumber)
    {
        $msId = $this->MSExternMovementIdProperty()->Value();

        $msSceneIdent = $this->MSDeactivateIdent($sceneNumber);
        $this->SetValueForIdent(msSceneIdent, GetValue($msId));        
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
        $this->LightStatusSwitchIdPropertyArray()->RegisterAll();
        $this->LightStatusDimIdPropertyArray()->RegisterAll();
        $this->SceneNamePropertyArray()->RegisterAll();
        $this->SwitchIdPropertyArray()->RegisterAll();
        $this->SwitchScenePropertyArray()->RegisterAll();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->CreateMotionTrigger();
        $this->CreateLinks();
        $this->CreateScenes();
        $this->CreateSceneProfile();
        $this->CreateSceneSelectionVar();
        $this->AddSaveButton();        
	}

    private function CreateMotionTrigger()
    {
        $this->RegisterLink(self::MSMainSwitchIdent, "BM sperren", $this->MSMainSwitchIdProperty()->Value(), self::PosMSDisabled);

        $this->RegisterTrigger(self::MSMainSwitchTriggerIdent, $this->MSMainSwitchIdProperty()->Value(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 1);
    }

    private function CreateLinks()
    {
        for($i=0; $i<self::MaxLights; $i++)
        {
            $this->CreateLightLink($i);
        }
    }

    private function CreateLightLink($lightNumber)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);
        $statusSwitchId = $this->LightStatusSwitchIdPropertyArray()->ValueAt($lightNumber);
        $statusDimId = $this->LightStatusDimIdPropertyArray()->ValueAt($lightNumber);

        $name = $this->LightNamePropertyArray()->ValueAt($lightNumber);        

        if($switchId == 0 && ($dimId != 0 || $statusSwitchId != 0 || $statusDimId != 0))
            throw new Exception("Switch id not set, but other ids for light number " . $lightNumber . "!");

        if(($switchId == 0) != ($statusSwitchId == 0))
            throw new Exception("Switch id requires status id for light number " . $lightNumber . "!");

        if(($dimId == 0) != ($statusDimId == 0))
            throw new Exception("Dim id requires status id for light number " . $lightNumber . "!");

        if($switchId != 0)
        {
            $ident = $this->LightSwitchIdent($lightNumber);
            $id = $this->RegisterVariableBoolean($ident, $name, "~Switch", self::PosLightSwitch);
            $this->EnableAction($ident);
            SetValue($id, GetValue($statusSwitchId));

            $triggerIdent = $ident . "Trigger";
            $script = 'SetValue(' . $id . ', $_IPS[\'VALUE\']); BL_CancelSave($_IPS[\'TARGET\']);';
            $this->RegisterTrigger($triggerIdent, $statusSwitchId, $script, self::TriggerTypeUpdate);
        }

        if($dimId != 0)
        {
            $ident = $this->LightDimIdent($lightNumber);
            $id = $this->RegisterVariableInteger($ident, $name, "~Intensity.100", self::PosLightSwitch);
            $this->EnableAction($ident);
            SetValue($id, GetValue($statusDimId));

            $triggerIdent = $ident . "Trigger";
            $script = 'SetValue(' . $id . ', $_IPS[\'VALUE\']); BL_CancelSave($_IPS[\'TARGET\']);';
            $this->RegisterTrigger($triggerIdent, $statusDimId, $script, self::TriggerTypeUpdate);
        }

    }

    private function CreateScenes()
    {
        for($i = 0; $i < self::MaxScenes; $i++)
        {
            $this->CreateSceneVars($i);
        }
    }

    private function CreateSceneVars($sceneNumber)
    {
        $sceneName = $this->SceneNamePropertyArray()->ValueAt($sceneNumber);
        
        if($sceneName === "")
            return;

        for($i=0; $i<self::MaxLights; $i++)
        {
            $this->CreateSceneLight($sceneNumber, $i);
        }

        $this->CreateMSDeactivate($sceneNumber);
    }

    private function CreateSceneLight($sceneNumber, $lightNumber)
    {
        $switchId = $this->LightSwitchIdPropertyArray()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdPropertyArray()->ValueAt($lightNumber);

        if($switchId != 0)
        {
            $name = $this->LightNamePropertyArray()->ValueAt($lightNumber);
            
            if($dimId == 0)
            {
                $ident = $this->SceneLightSwitchIdent($lightNumber, $sceneNumber);
                $this->RegisterVariableBoolean($ident, $name . $sceneNumber . "Switch", "~Switch", 0);
                $this->EnableAction($ident);
                IPS_SetHidden($ident, true);
            }
            else
            {
                $ident = $this->SceneLightDimIdent($lightNumber, $sceneNumber);
                $this->RegisterVariableInteger($ident, $name . $sceneNumber . "Dim", "~Intensity.100", 0);
                $this->EnableAction($ident);
                IPS_SetHidden($ident, true);
            }
        }
    }

    private function CreateMSDeactivate($sceneNumber)
    {
        $ident = $this->MSDeactivateIdent($sceneNumber);
        $id = $this->RegisterVariableBoolean($ident, "BMSperren" . $sceneNumber, "~Switch");
        IPS_SetHidden($ident, true);
    }

    private function CreateSceneProfile()
    {        
        @IPS_DeleteVariableProfile($this->SceneProfileString());
        IPS_CreateVariableProfile($this->SceneProfileString(), 1);
        
        for($i = 0; $i < self::MaxScenes; $i++)
        {
            if($this->SceneNamePropertyArray()->ValueAt($i) !== "")
                IPS_SetVariableProfileAssociation($this->SceneProfileString(), $i, $this->SceneNamePropertyArray()->ValueAt($i), "", 0xFFFFFF);
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $id = $this->RegisterVariableInteger(self::CurrentSceneIdent, "Szene", $this->SceneProfileString());
        $this->EnableAction(self::CurrentSceneIdent);
        IPS_SetPosition($id, self::PosSceneSelection);
    }

    private function LoadFromScene($sceneNumber)
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->LoadLightFromScene($lightNumber, $sceneNumber);
        }            
        $this->LoadMSDeactivateFromScene($sceneNumber);
    }

    private function AddSaveButton() 
    {
        $id = $this->RegisterVariableInteger(self::SaveToSceneIdent, "Speichern unter:", $this->SceneProfileString(), self::PosSaveSceneButton);
        $this->EnableAction(self::SaveToSceneIdent);

        $this->RegisterScript(self::SaveSceneIdent, "Szene speichern", 
            "<? BL_StartSave(" . $this->InstanceID . ");?>",
            self::PosSaveSceneButton);
        //$this->EnableAction(self::SaveSceneIdent);

        $this->CancelSave();
    }

    public function StartSave()
    {
        $id = IPS_GetObjectIDByIdent(self::SaveToSceneIdent, $this->InstanceID);
        IPS_SetHidden($id, false);

        $id = IPS_GetObjectIDByIdent(self::SaveSceneIdent, $this->InstanceID);
        IPS_SetHidden($id, true);        
    }

    private function SaveToScene($sceneNumber)
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->SaveLightToScene($lightNumber, $sceneNumber);
        }
        $this->SaveMSDeactivateToScene($sceneNumber);

        $this->CancelSave();
    }

    public function CancelSave()
    {
        $id = IPS_GetObjectIDByIdent(self::SaveToSceneIdent, $this->InstanceID);
        IPS_SetHidden($id, true);

        $id = IPS_GetObjectIDByIdent(self::SaveSceneIdent, $this->InstanceID);
        IPS_SetHidden($id, false);        
    }

    public function RequestAction($ident, $value) 
    {
        $lightNumber = $this->LightNumberForLightSwitchIdent($ident);
        if($lightNumber !== false)
        {
            $this->SetLightSwitch($lightNumber, $value);
            $this->CancelSave();
            return;
        }

        $lightNumber = $this->LightNumberForLightDimIdent($ident);
        if($lightNumber !== false)
        {
            $this->SetLightDim($lightNumber, $value);
            $this->CancelSave();
            return;
        }

        switch($ident) {
            case self::MSMainSwitchIdent:
                $this->SetMSDeactivate($value);
                $this->CancelSave();
                break;

            case self::CurrentSceneIdent:
                $this->SetValueForIdent($ident, $value);
                $this->LoadFromScene($value);
                $this->CancelSave();
                break;

            case self::SaveSceneIdent:
                IPS_LogMessage("BetterLight", "RequestAction: self::SaveSceneIdent");

                $this->StartSave();
                break;

            case self::SaveToSceneIdent:
                $this->SaveToScene($value);
                break;

            default:
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    public function MSMainSwitchEvent()
    {
        $msId = $this->MSMainSwitchIdProperty()->Value();
        $turnOn = GetValue($msId);

        if($turnOn)
        {
            $this->LoadFromScene($this->CurrentSceneNumber());
        }
        else
        {
            $this->TurnOffAll();
        }
    }

    public function TurnOffAll()
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->SetLight($lightNumber, 0);
        }
    }

}
?>