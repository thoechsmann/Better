<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class BetterLight extends BetterBase {

    const MaxLights = 8;
    const MaxScenes = 4;
    const MaxSwitches = 4;

    const StrLight = "light";
    const StrScene = "scene";
    const StrSwitch = "switch";
    const StrDim = "Dim";
    const StrLink = "Link";

    const PosSceneSelection = 1;
    const PosMSDisabled = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosSaveSceneButton = 5;
    const PosSceneScheduler = 6;

    const SaveSceneIdent = "SaveScene";
    const MSMainSwitchTriggerIdent = "MSMainSwitchTrigger";
    const SceneSchedulerIdent = "SceneScheduler";
    const MSDeactivateIdent = "MSDeactivate";

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

    private function LightNameProperties()
    {        
        return new PropertyArrayString($this, self::StrLight . "Name", self::MaxLights);
    }

    private function LightSwitchIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "SwitchId", self::MaxLights);
    }

    private function LightDimIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "DimId", self::MaxLights);
    }

    private function LightStatusSwitchIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "StatusSwitchId", self::MaxLights);
    }

    private function LightStatusDimIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "StatusDimId", self::MaxLights);
    }

    private function SceneNameProperties()
    {        
        return new PropertyArrayString($this, self::StrScene . "Name", self::MaxScenes);
    }

    private function SceneColorProperties()
    {        
        return new PropertyArrayString($this, self::StrScene . "Color", self::MaxScenes);
    }

    private function SwitchIdProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Id", self::MaxSwitches);
    }

    private function SwitchSceneProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Scene", self::MaxSwitches);
    }

    // Variables
    private function LightSwitchVars()
    {
        return new VariableArray($this, self::StrLight . self::StrSwitch, self::MaxLights);
    }

    private function LightDimVars()
    {
        return new VariableArray($this, self::StrLight . self::StrDim, self::MaxLights);
    }

    private function CurrentSceneVar()
    {
        return new Variable($this, parent::PersistentPrefix . "CurrentScene");
    }

    private function SaveToSceneVar()
    {
        return new Variable($this, "SaveToScene");
    }

    private function IdentToIgnoreOnNextTurnOnVar()
    {
        return new Variable($this, "IdentToIgnoreOnNextTurnOn");
    }

    private function SceneLightSwitchVars()
    {
        return new VariableArray($this, 
            parent::PersistentPrefix . self::StrScene . self::StrSwitch . self::StrLight, 
            self::MaxLights, 
            self::MaxScenes);
    }

    private function SceneLightDimVars()
    {
        return new VariableArray($this, 
            parent::PersistentPrefix . self::StrScene . self::StrDim . self::StrLight, 
            self::MaxLights, 
            self::MaxScenes);
    }

    // Backing
    private function LightSwitchBacking($lightNumber)
    {
        $getterId = $this->LightStatusSwitchIdProperties()->ValueAt($lightNumber);
        $setterId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $displayIdent = $this->LightSwitchVars()->At($lightNumber)->Ident();

        return new Backing($this, $displayIdent, $getterId, $setterId, Backing::EIBTypeSwitch);
    }

    private function LightDimBacking($lightNumber)
    {
        $getterId = $this->LightStatusDimIdProperties()->ValueAt($lightNumber);
        $setterId = $this->LightDimIdProperties()->ValueAt($lightNumber);
        $displayIdent = $this->LightDimVars()->At($lightNumber)->Ident();

        return new Backing($this, $displayIdent, $getterId, $setterId, Backing::EIBTypeScale);
    }

    // private function MSDeactivateIdent($sceneNumber)
    // {
    //     return parent::PersistentPrefix . "scene". $sceneNumber ."MSDeactivate";
    // }
    
    private function SceneCount()
    {
        $count = 0;

        for($i = 0; $i < self::MaxScenes; $i++)
        {
            if($this->SceneNameProperties()->ValueAt($i) !== "")
                $count++;
        }   

        return $count;
    }

    private function SetSceneProfileString()
    {
        return "BL_setScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function SaveSceneProfileString()
    {
        return "BL_saveScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function LoadLightFromScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $var = $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber);
            $backing = $this->LightSwitchBacking($lightNumber);
        }
        else
        {
            $var = $this->SceneLightDimVars()->At($lightNumber, $sceneNumber);
            $backing = $this->LightDimBacking($lightNumber);
        }

        $ident = $backing->DisplayIdent();
        $identToIgnore = $this->IdentToIgnoreOnNextTurnOnVar()->GetValue();

        if($ident == $identToIgnore)
        {
            $value = $var->GetDisplayValue();
        }
        else
        {
            $value = $var->GetValue();
        }

        $backing->SetValue($value);
    }

    private function SaveLightToScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $value = $this->LightSwitchBacking($lightNumber)->GetValue();
            $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber)->SetValue($value);
        }
        else
        {
            $value = $this->LightDimBacking($lightNumber)->GetValue();
            $this->SceneLightDimVars()->At($lightNumber, $sceneNumber)->SetValue($value);
        }
    }

    private function IsMSDeactivated()
    {
        $msId = $this->MSDeactivateIdProperty()->Value();

        if($msId == 0)
            return false;

        return GetValue($msId);
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
        // $msId = $this->MSDeactivateIdProperty()->Value();

        // $msSceneIdent = $this->MSDeactivateIdent($sceneNumber);
        // $msSceneValue = $this->GetValueForIdent($msSceneIdent);

        // EIB_Switch(IPS_GetParent($msId), $msSceneValue);
    }

    // private function SaveMSDeactivateToScene($sceneNumber)
    // {
    //     $msId = $this->MSDeactivateIdProperty()->Value();

    //     $msSceneIdent = $this->MSDeactivateIdent($sceneNumber);
    //     $this->SetValueForIdent($msSceneIdent, GetValue($msId));        
    // }

    private function SetMSExternMovement()
    {
        $msId = $this->MSExternMovementIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), true);
        }
    }

    public function MainSwitchStatus()
    {
        $msId = $this->MSMainSwitchIdProperty()->Value();
        return GetValue($msId);
    }

	public function Create() 
    {
		parent::Create();		
        
        $this->MSMainSwitchIdProperty()->Register();
        $this->MSDeactivateIdProperty()->Register();
        $this->MSExternMovementIdProperty()->Register();

        $this->LightNameProperties()->RegisterAll();
        $this->LightSwitchIdProperties()->RegisterAll();
        $this->LightDimIdProperties()->RegisterAll();
        $this->LightStatusSwitchIdProperties()->RegisterAll();
        $this->LightStatusDimIdProperties()->RegisterAll();

        $this->SwitchIdProperties()->RegisterAll();
        $this->SwitchSceneProperties()->RegisterAll();

        $this->SceneNameProperties()->RegisterAll();
        $this->SceneColorProperties()->RegisterAll();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        $this->CreateMotionTrigger();
        $this->CreateLinks();
        $this->CreateScenes();
        $this->CreateSceneProfiles();
        $this->CreateSceneSelectionVar();
        $this->CreateSceneScheduler();
        $this->AddSaveButton();        
	}

    private function CreateMotionTrigger()
    {
        // $this->RegisterLink(self::MSDeactivateIdent, "BM sperren", $this->MSDeactivateIdProperty()->Value(), self::PosMSDisabled);

        $this->RegisterTrigger(self::MSMainSwitchTriggerIdent, $this->MSMainSwitchIdProperty()->Value(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', self::TriggerTypeUpdate);
    }

    private function CreateLinks()
    {
        $this->IdentToIgnoreOnNextTurnOnVar()->RegisterVariableString();
        $this->IdentToIgnoreOnNextTurnOnVar()->SetValue("");

        for($i=0; $i<self::MaxLights; $i++)
        {
            $this->CreateLightLink($i);
        }
    }

    private function CreateLightLink($lightNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        $name = $this->LightNameProperties()->ValueAt($lightNumber);        

        if($switchId != 0)
        {
            $switchBacking = $this->LightSwitchBacking($lightNumber);

            $lightSwitch = $this->LightSwitchVars()->At($lightNumber);
            $lightSwitch->RegisterVariableBoolean($name, "~Switch", self::PosLightSwitch);
            $lightSwitch->EnableAction();
            $lightSwitch->SetValue($switchBacking->GetValue());
            if($dimId != 0)
            {
                $lightSwitch->SetHidden(true);
            }

            $switchBacking->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
        }

        if($dimId != 0)
        {
            $dimBacking = $this->LightDimBacking($lightNumber);

            $lightDim = $this->LightDimVars()->At($lightNumber);
            $lightDim->RegisterVariableInteger($name, "~Intensity.100", self::PosLightSwitch);
            $lightDim->EnableAction();
            $lightDim->SetValue($dimBacking->GetValue());

            $dimBacking->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
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
        if($sceneNumber == 0)
        {
            $this->SceneNameProperties()->At($sceneNumber)->SetValue("Aus");
            $this->SceneColorProperties()->At($sceneNumber)->SetValue("0x000000");
        }

        $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);
        
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
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId != 0)
        {
            $name = $this->LightNameProperties()->ValueAt($lightNumber);
            
            if($dimId == 0)
            {
                $sceneLight = $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber);
                $sceneLight->RegisterVariableBoolean($name . $sceneNumber . "Switch", "~Switch");
            }
            else
            {
                $sceneLight = $this->SceneLightDimVars()->At($lightNumber, $sceneNumber);
                $sceneLight->RegisterVariableInteger($name . $sceneNumber . "Dim", "~Intensity.100");
            }

            $sceneLight->EnableAction();
            $sceneLight->SetHidden(true);
        }
    }

    private function CreateMSDeactivate($sceneNumber)
    {
        // $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);
        
        // if($sceneName == "")
        //     return;

        // $ident = $this->MSDeactivateIdent($sceneNumber);
        // $id = $this->RegisterVariableBoolean($ident, "BMSperren" . $sceneName, "~Switch", self::PosMSDisabled);
        // $this->EnableAction($ident);
        // IPS_SetHidden($id, true);        
    }

    private function CreateSceneProfiles()
    {   
        $setProfile = $this->SetSceneProfileString();
        $saveProfile = $this->SaveSceneProfileString();

        @IPS_DeleteVariableProfile($setProfile);
        IPS_CreateVariableProfile($setProfile, 1);

        @IPS_DeleteVariableProfile($saveProfile);
        IPS_CreateVariableProfile($saveProfile, 1);
        
        for($sceneNumber = 0; $sceneNumber < self::MaxScenes; $sceneNumber++)
        {
            $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);

            if($sceneName != "")
            {
                $sceneColor = intval($this->SceneColorProperties()->ValueAt($sceneNumber), 0);

                IPS_SetVariableProfileAssociation($setProfile, $sceneNumber, $sceneName, "", $sceneColor);

                if($sceneNumber != 0)
                {
                    IPS_SetVariableProfileAssociation($saveProfile, $sceneNumber, $sceneName, "", $sceneColor);
                }
            }
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $currentScene = $this->CurrentSceneVar();
        $currentScene->RegisterVariableInteger("Szene", $this->SetSceneProfileString());
        $currentScene->EnableAction();
        $currentScene->SetPosition(self::PosSceneSelection);
    }

    private function CreateSceneScheduler()
    {
        // Scheduled Event
        $schedulerId = $this->RegisterScheduler(parent::PersistentPrefix . self::SceneSchedulerIdent, "Szenen Zeiten");
        IPS_SetIcon($schedulerId, "Calendar");
        IPS_SetHidden($schedulerId, false);
        IPS_SetPosition($schedulerId, self::PosSceneScheduler);
        IPS_SetEventScheduleGroup($schedulerId, 0, 127); //Mo - Fr (1 + 2 + 4 + 8 + 16)

        for($sceneNumber = 0; $sceneNumber<self::MaxScenes; $sceneNumber++)
        {
            $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);
            
            if($sceneName != "")
            {
                $sceneColor = intval($this->SceneColorProperties()->ValueAt($sceneNumber), 0);

                IPS_SetEventScheduleAction($schedulerId, $sceneNumber, $sceneName, $sceneColor, 
                    "BL_SetScene(\$_IPS['TARGET'], $sceneNumber);");
            }
        }
    }

    private function AddSaveButton() 
    {
        $saveToScene = $this->SaveToSceneVar();
        $saveToScene->RegisterVariableInteger("Speichern unter:", $this->SaveSceneProfileString(), self::PosSaveSceneButton);
        $saveToScene->EnableAction();
        $saveToScene->SetValue(-1);

        $this->RegisterScript(self::SaveSceneIdent, "Szene speichern", 
            "<? BL_StartSave(" . $this->InstanceID . ");?>",
            self::PosSaveSceneButton);

        $this->CancelSave();
    }

    public function StartSave()
    {
        $this->SaveToSceneVar()->SetHidden(false);

        $id = $this->GetIDForIdent(self::SaveSceneIdent);
        IPS_SetHidden($id, true);        
    }

    private function SaveToScene($sceneNumber)
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->SaveLightToScene($lightNumber, $sceneNumber);
        }
        // $this->SaveMSDeactivateToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        $this->ShowMSDeactivate($sceneNumber);

        $this->LoadMSDeactivateFromScene($sceneNumber);

        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->LoadLightFromScene($lightNumber, $sceneNumber);
        }            
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        $this->CurrentSceneVar()->SetValue($sceneNumber);
        $this->CancelSave();
        $isOn = $this->MainSwitchStatus();

        if($isOn)
        {
            $this->LoadFromScene($sceneNumber);
        }
        else if($turnOn)
        {
            $this->SetMSExternMovement();
        }
    }

    public function CancelSave()
    {
        $this->SaveToSceneVar()->SetHidden(true);

        $id = $this->GetIDForIdent(self::SaveSceneIdent);
        IPS_SetHidden($id, false);        
    }

    private function ShowMSDeactivate($sceneNumber)
    {
        // for($scene = 0; $scene<self::MaxScenes; $scene++)
        // {
        //     $ident = $this->MSDeactivateIdent($scene);
        //     $id = $this->GetIDForIdent($ident);
        //     IPS_SetHidden($id, $scene != $sceneNumber);
        // }
    }

    public function RequestAction($ident, $value) 
    {
        $lightNumber = $this->LightSwitchVars()->GetIndexForIdent($ident);
        if($lightNumber !== false)
        {
            $this->LightSwitchBacking($lightNumber)->SetValue($value);
            $this->CancelSave();            
            $isOn = $this->MainSwitchStatus();
            if(!$isOn)
            {
                $this->IdentToIgnoreOnNextTurnOnVar()->SetValue($ident);
                $this->SetMSExternMovement();
            }
            return;
        }

        $lightNumber = $this->LightDimVars()->GetIndexForIdent($ident);
        if($lightNumber !== false)
        {
            $this->LightDimBacking($lightNumber)->SetValue($value);
            $this->CancelSave();
            $isOn = $this->MainSwitchStatus();
            if(!$isOn)
            {
                $this->IdentToIgnoreOnNextTurnOnVar()->SetValue($ident);
                $this->SetMSExternMovement();
            }
            return;
        }

        switch($ident) {
            // case self::MSDeactivateIdent:
            //     $this->SetMSDeactivate($value);
            //     $this->CancelSave();
            //     break;

            case self::SaveSceneIdent:
                $this->StartSave();
                break;

            case $this->CurrentSceneVar()->Ident():
                $this->SetScene($value, true);
                break;

            case $this->SaveToSceneVar()->Ident():
                $this->SaveToScene($value);
                break;

            default:
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    public function MSMainSwitchEvent()
    {
        $turnOn = $this->MainSwitchStatus();

        IPS_LogMessage("BetterLight", "MSMainSwitchEvent TurnOn: $turnOn MSDeactivated: " . $this->IsMSDeactivated());

        if($turnOn)
        {
            $this->LoadFromScene($this->CurrentSceneVar()->GetValue());
        }
        else
        {
            $this->TurnOffAll();
        }

        $this->IdentToIgnoreOnNextTurnOnVar()->SetValue("");
    }

    public function TurnOffAll()
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            if($this->LightSwitchIdProperties()->ValueAt($lightNumber) != 0)
                $this->LightSwitchBacking($lightNumber)->SetValue(false);
        }
    }

}
?>