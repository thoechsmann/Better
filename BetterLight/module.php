<?
require_once(__DIR__ . "/Light.php");
require_once(__DIR__ . "/Scene.php");
require_once(__DIR__ . "/SceneSwitch.php");
require_once(__DIR__ . "/MotionSensor.php");
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class BetterLight extends BetterBase {

    const MaxDimLights = 6;
    const MaxSwitchLights = 2;
    const MaxRGBLights = 2;
    const MaxSceneSwitches = 4;
    const MaxScenes = 4;

    const StrSwitch = "switch";

    const PosSceneSelection = 1;
    const PosMSLock = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosLightRGB = 5;
    const PosSaveSceneButton = 6;
    const PosSceneScheduler = 7;

    const OffSceneNumber = 0;
    const DefaultSceneNumber = 1;

    const OffTimerTime = 15;

    // Variables

    private function CurrentSceneVar()
    {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . "CurrentScene");

        // if(!isset($currentSceneVar))
        //     $currentSceneVar = new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . "CurrentScene");

        // return $currentSceneVar;
    }

    private function SaveToSceneVar()
    {
        return new IPSVarInteger($this->InstanceID(), "SaveSceneSelect");
    }

    private function IdendTriggerdTurnOnVar()
    {
        return new IPSVarString($this->InstanceID(), "IdendTriggerdTurnOn");
    }

    private function IdendTriggerdTurnOnBooelanValueVar()
    {
        return new IPSVarBoolean($this->InstanceID(), "IdendTriggerdTurnOnBooelanValue");
    }

    private function IdendTriggerdTurnOnIntegerValueVar()
    {
        return new IPSVarInteger($this->InstanceID(), "IdendTriggerdTurnOnIntegerValue");
    }

    private function IdendTriggerdTurnOnFloatValueVar()
    {
        return new IPSVarFloat($this->InstanceID(), "IdendTriggerdTurnOnFloatValue");
    }

    // Scripts

    private function SaveSceneScript()
    {
        return new IPSScript($this->InstanceID(), "SaveSceneStart");
    }

    // Events

    private function SceneScheduler()
    {
        return new IPSEventScheduler($this->InstanceID(), parent::PersistentPrefix . "SceneScheduler");
    }

    private function OffTimer()
    {
        return new IPSEventCyclic($this->InstanceID(), "OffTimer");
    }

    //
    private function DimLights()
    {
        return new LightArray($this, self::MaxDimLights, LightArray::TypeDim);
    }

    private function SwitchLights()
    {
        return new LightArray($this, self::MaxSwitchLights, LightArray::TypeSwitch);
    }

    private function RGBLights()
    {
        return new LightArray($this, self::MaxRGBLights, LightArray::TypeRGB);
    }

    private function Scenes()
    {
        return new SceneArray($this, self::MaxScenes);
    }

    private function SceneSwitches()
    {
        return new SceneSwitchArray($this, self::MaxSceneSwitches);
    }

    private function MotionSensor()
    {
        return new MotionSensor($this);
    }
    //

    private function SetSceneProfileString()
    {
        return "BL_setScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function SaveSceneProfileString()
    {
        return "BL_saveScenes_" . $this->GetName() . $this->InstanceID;
    }

    public function Create() 
    {
        parent::Create();       

        $this->MotionSensor()->RegisterProperties();

        $this->DimLights()->RegisterProperties();
        $this->SwitchLights()->RegisterProperties();
        $this->RGBLights()->RegisterProperties();

        $this->Scenes()->RegisterProperties();
        $this->SceneSwitches()->RegisterProperties();

        // Set default values
        $this->Scenes()->At(self::OffSceneNumber)->SetName("Aus");
        $this->Scenes()->At(self::OffSceneNumber)->SetColor("0x000000");

        $this->Scenes()->At(self::DefaultSceneNumber)->SetName("Standard");
        $this->Scenes()->At(self::DefaultSceneNumber)->SetColor("0x00FF00");
    }
    
    public function ApplyChanges() 
    {
        parent::ApplyChanges();
        
        $this->CreateMotionSensor();
        $this->CreateLights();
        $this->CreateSceneProfiles();
        $this->CreateSceneSelectionVar();
        $this->CreateSceneScheduler();
        $this->CreateSceneSwitches();
        $this->CreateSaveButton();    
        $this->CreateOffTimer();    

        $this->IdendTriggerdTurnOnVar()->Register();
        $this->IdendTriggerdTurnOnVar()->SetValue("");
        $this->IdendTriggerdTurnOnVar()->Hide();

        $this->IdendTriggerdTurnOnBooelanValueVar()->Register();
        $this->IdendTriggerdTurnOnBooelanValueVar()->Hide();

        $this->IdendTriggerdTurnOnFloatValueVar()->Register();
        $this->IdendTriggerdTurnOnFloatValueVar()->Hide();

        $this->IdendTriggerdTurnOnIntegerValueVar()->Register();
        $this->IdendTriggerdTurnOnIntegerValueVar()->Hide();

        // Set defaults
        $this->MotionSensor()->SetSceneLock(self::OffSceneNumber, true);
        $this->MotionSensor()->SetSceneLock(self::DefaultSceneNumber, false);

    }

    private function CreateMotionSensor()
    {
        $ms = $this->MotionSensor();
        $ms->RegisterVariables($this->Scenes()->Count(), self::PosMSLock);
        $ms->RegisterTriggers();

    }

    private function CreateLights()
    {
        $sceneCount = $this->Scenes()->Count();

        $this->dimLights()->RegisterVariables($sceneCount, self::PosLightDim);
        $this->SwitchLights()->RegisterVariables($sceneCount, self::PosLightSwitch);
        $this->RGBLights()->RegisterVariables($sceneCount, self::PosLightRGB);

        $this->dimLights()->RegisterTriggers();      
        $this->SwitchLights()->RegisterTriggers();      
        $this->RGBLights()->RegisterTriggers();
    }

    private function CreateSceneProfiles()
    {   
        $setProfile = $this->SetSceneProfileString();
        $saveProfile = $this->SaveSceneProfileString();

        @IPS_DeleteVariableProfile($setProfile);
        IPS_CreateVariableProfile($setProfile, 1);

        @IPS_DeleteVariableProfile($saveProfile);
        IPS_CreateVariableProfile($saveProfile, 1);
        
        for($sceneNumber = 0; $sceneNumber < $this->Scenes()->Count(); $sceneNumber++)
        {
            $scene = $this->Scenes()->At($sceneNumber);

            IPS_SetVariableProfileAssociation($setProfile, $sceneNumber, $scene->Name(), "", $scene->Color());

            if($sceneNumber != 0)
            {
                IPS_SetVariableProfileAssociation($saveProfile, $sceneNumber, $scene->Name(), "", $scene->Color());
            }
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $currentScene = $this->CurrentSceneVar();
        $currentScene->Register("Szene", $this->SetSceneProfileString(), self::PosSceneSelection);
        $currentScene->EnableAction();
    }

    private function CreateSceneScheduler()
    {
        // Scheduled Event
        $scheduler = $this->SceneScheduler();
        $scheduler->Register("Szenen Zeiten", self::PosSceneScheduler);
        $scheduler->SetIcon("Calendar");
        $scheduler->Show();
        $scheduler->SetGroup(0, 127); //Mo - Fr (1 + 2 + 4 + 8 + 16)

        for($sceneNumber = 0; $sceneNumber<$this->Scenes()->Count(); $sceneNumber++)
        {
            $scene = $this->Scenes()->At($sceneNumber);
            
            $scheduler->SetAction($sceneNumber, $scene->Name(), $scene->Color(), 
                "BL_SetScene(\$_IPS['TARGET'], $sceneNumber);");
        }
    }

    private function CreateSceneSwitches()
    {
        $this->SceneSwitches()->RegisterTriggers();
    }

    private function CreateSaveButton() 
    {
        $saveToScene = $this->SaveToSceneVar();
        $saveToScene->Register("Speichern unter:", $this->SaveSceneProfileString(), self::PosSaveSceneButton);
        $saveToScene->EnableAction();
        $saveToScene->SetValue(-1);

        $this->SaveSceneScript()->Register("Szene speichern", "<? BL_StartSave(" . $this->InstanceID . ");?>", self::PosSaveSceneButton);

        $this->CancelSave();
    }

    private function CreateOffTimer()
    {
        $script = "BL_BackToCurrentScene(" . $this->InstanceId() . ");";

        $this->OffTimer()->Register($script);
        $this->OffTimer()->Hide();
    }

    public function StartSave()
    {
        IPS_LogMessage("BL","StartSave() ");
        $this->SaveToSceneVar()->Show();
        $this->SaveSceneScript()->Hide();
    }

    private function SaveToScene($sceneNumber)
    {
        IPS_LogMessage("BL","SaveToScene(sceneNumber = $sceneNumber)");

        $this->DimLights()->SaveToScene($sceneNumber);
        $this->SwitchLights()->SaveToScene($sceneNumber);
        $this->RGBLights()->SaveToScene($sceneNumber);
        
        $this->MotionSensor()->SaveToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        IPS_LogMessage("BL","LoadFromScene(sceneNumber = $sceneNumber)");

        $triggerIdent = $this->IdendTriggerdTurnOnVar()->GetValue();
        $triggerBoolValue = $this->IdendTriggerdTurnOnFloatValueVar()->GetValue();

        $this->DimLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        $this->SwitchLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        $this->RGBLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);

        // Motion Sensor is set in SetScene.
    }

    public function BackToCurrentScene()
    {
        $this->SetScene($this->CurrentSceneVar()->GetValue(), false);
    }

    public function ToggleScene($sceneNumber)
    {
        $currentScene = $this->CurrentSceneVar()->GetValue();

        if($currentScene == $sceneNumber)
        {
            $this->SetScene(self::DefaultSceneNumber, true);
        }
        else
        {
            $this->SetScene($sceneNumber, true);
        }
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        IPS_LogMessage("BL","SetScene(sceneNumber = $sceneNumber, turnOn = $turnOn)");
        $this->CurrentSceneVar()->SetValue($sceneNumber);
        $this->CancelSave();
 
        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();

        if($isOn || $turnOn)
        {
            $ms->LoadFromScene($sceneNumber);
        }

        if($isOn)
        {
            // Do not load scene when ms is activated and light is on as turning ms lock on will send light status event.
            // This event will be catched and used to set the current scene.
            if(!$ms->IsLocked())
                $this->LoadFromScene($sceneNumber);

        }
        else if($turnOn)
        {
            if($ms->IsLocked())
                $this->LoadFromScene($sceneNumber);
            else
                $ms->TriggerExternMovement();
        }
    }

    public function CancelSave()
    {
        IPS_LogMessage("BL","CancelSave() ");
        $this->SaveToSceneVar()->Hide();
        $this->SaveSceneScript()->Show();
    }

    // FIX: Remove storeVar. Save everything in a string.
    private function SetBackedValue($backing, $value, $storeVar)
    {
        $this->CancelSave();            

        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();
        $IsMSLocked = $ms->IsLocked();

        if($IsMSLocked)
        {
            // if MS is locked we do not get a turn on event.
            $backing->SetValue($value);
            $this->SetScene($this->CurrentSceneVar()->GetValue());
        }
        else
        {
            if(!$isOn)
            {
                $this->IdendTriggerdTurnOnVar()->SetValue($backing->DisplayIdent());
                $storeVar->SetValue($value);
                $ms->TriggerExternMovement();
            }

            $backing->SetValue($value);
        }
    }

    public function RequestAction($ident, $value) 
    {
        IPS_LogMessage("BL", "RequestAction - ident:$ident, value:$value");

        if($this->RequestActionForLight($ident, $value))
            return;

        switch($ident) {
            case $this->SaveSceneScript()->Ident():
                $this->StartSave();
                break;

            case $this->CurrentSceneVar()->Ident():
                $this->SetScene($value, true);
                break;

            case $this->SaveToSceneVar()->Ident():
                IPS_LogMessage("BL", "RequestAction SaveToSceneVar - ident:$ident, value:$value");
                $this->SaveToScene($value);
                break;

            default:
                IPS_LogMessage("BL", "RequestAction default - ident:$ident, value:$value");
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    private function RequestActionForLight($ident, $value)
    {
        $switchLightNumber = $this->SwitchLights()->GetIndexForDisplayIdent($ident);
        if($switchLightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction SwitchLight - ident:$ident, value:$value");
            $light = $this->SwitchLights($switchLightNumber);
            $identTrigger = $this->IdendTriggerdTurnOnBooelanValueVar();
            $this->SetBackedValue($light->DisplayVarBacking(), $value, $identTrigger);
            return true;
        }

        $dimLightNumber = $this->DimLights()->GetIndexForDisplayIdent($ident);
        if($dimLightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction DimLight - ident:$ident, value:$value");
            $light = $this->DimLights($dimLightNumber);
            $identTrigger = $this->IdendTriggerdTurnOnFloatValueVar();
            $this->SetBackedValue($light->DisplayVarBacking(), $value, $identTrigger);
            return true;
        }

        $rgbLightNumber = $this->RGBLights()->GetIndexForDisplayIdent($ident);
        if($rgbLightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction RGBLight - ident:$ident, value:$value");
            $light = $this->RGBLights($rgbLightNumber);
            $identTrigger = $this->IdendTriggerdTurnOnIntegerValueVar();
            $this->SetBackedValue($light->DisplayVarBacking(), $value, $identTrigger);
            return true;
        }


        return false;
    }

    public function MSMainSwitchEvent()
    {
        $ms = $this->MotionSensor();
        $turnOn = $ms->IsMainSwitchOn();

        IPS_LogMessage("BL", "MSMainSwitchEvent - turnOn:$turnOn, isMSLocked:" . $ms->IsLocked());

        if($turnOn)
        {
            $this->LoadFromScene($this->CurrentSceneVar()->GetValue());
        }
        else
        {
            $this->TurnOffAll();
        }

        $this->IdendTriggerdTurnOnVar()->SetValue("");
    }

    private function TurnOffAll()
    {
        $this->DimLights()->TurnOff();
        $this->SwitchLights()->TurnOff();
        $this->RGBLights()->TurnOff();
    }

    public function TurnOff()
    {
        $currentScene = $this->CurrentSceneVar()->GetValue();
        $this->SetScene(self::OffSceneNumber, true);
        $this->CurrentSceneVar()->SetValue($currentScene);
        
        $this->OffTimer()->StartTimer(self::OffTimerTime);
    }

}
?>
