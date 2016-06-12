<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");
require_once(__DIR__ . "/../IPSVar.php");

class LightArray {
    const TypeSwitch = 0;
    const TypeDim = 1;
    const TypeRGB = 2;

    private $size;
    private $type;
    private $module;

    public function __construct($module, $size, $type)
    {
        $this->module = $module;
        $this->size = $size;
        $this->type = $type;
    }

    public function Count()
    {
        $count = 0;
        
        for($i=0; $i<$this->size; $i++)
        {
            $light = $this->At($i);

            if(!$light->IsDefined())
            {
                return $count;
            }

            $count++;
        }

        return $count;
    }

    public function At($index)
    {
        switch($this->type)
        {
            case LightArray::TypeSwitch:
                return new SwitchLight($this->module, $index);
                break;
            case LightArray::TypeDim:
                return new DimLight($this->module, $index);
                break;
            case LightArray::TypeRGB:
                return new RGBLight($this->module, $index);
                break;
            default: 
                IPS_LogMessage("BL", "LightArray::At - Unsupported type: $type");
        }
    }

    public function GetIndexForDisplayIdent($ident)
    {
        for($i = 0; $i<$this->size; $i++)
        {
            $var = $this->At($i);
            if($var->IsDisplayVar($ident))
                return $i;
        }

        return false;
    }

    public function RegisterProperties()
    {
        for($i=0; $i<$this->size; $i++)
        {
            $this->At($i)->RegisterProperties();
        }
    }

    public function RegisterVariables($sceneCount, $position)
    {
        for($i=0; $i<$this->Count(); $i++)
        {
            $this->At($i)->RegisterVariables($sceneCount, $position);
        }
    }

    public function RegisterTriggers()
    {
        for($i=0; $i<$this->Count(); $i++)
        {
            $this->At($i)->RegisterTriggers();
        }
    }

    public function SaveToScene($sceneNumber)
    {
        for($i = 0; $i < $this->Count(); $i++)
        {
            $this->At($i)->SaveToScene($sceneNumber);
        }
    }

    public function LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue)
    {
        for($i = 0; $i < $this->Count(); $i++)
        {
            $this->At($i)->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        }
    }

    public function TurnOff()
    {
        for($i = 0; $i < $this->Count(); $i++)
        {
            $this->At($i)->TurnOff();
        }
    }

}

abstract class Light {
    const StrScene = "Scene";

    protected $index;
    protected $module;
    protected $prefix;

    public function __construct($module, $index, $prefix) {
        $this->module = $module;
        $this->index = $index;
        $this->prefix = $prefix;
    }

    // Properties

    protected function NameProp()
    {        
        return new PropertyString($this->module, $this->prefix . $this->index . "Name");
    }   

    protected function SwitchIdProp()
    {        
        return new PropertyInteger($this->module, $this->prefix . $this->index . "SwitchId");
    }

    // Variables

    abstract protected function DisplayVar();

    protected function DisplayVarName()
    {
        return $this->prefix . $this->index . "DisplayVar";
    }

    public function IsDisplayVar($ident)
    {
        return $ident == $this->DisplayVar()->Ident();
    }

    abstract protected function SceneVars($sceneNumber);

    protected function SceneVarName($sceneNumber);
    {
        return BetterBase::PersistentPrefix . 
            $this->prefix . $this->index . 
            self::StrScene . $sceneNumber . 
            "Value";
    }

    // Register

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->SwitchIdProp()->Register();
    }

    public function RegisterVariables($sceneCount, $position)
    {    
        $this->RegisterDisplayVar($position);
        $this->RegisterSceneVars($sceneCount);
    }

    private function RegisterDisplayVar($position)
    {
        $name = $this->Name();
        $var = $this->DisplayVar();
        $var->Register($name, "", $position);
        $var->EnableAction();
    }

    private function RegisterSceneVars($sceneCount)
    {
        for($i = 0; $i<$sceneCount; $i++)
        {
            $sceneLight = $this->SceneVars($i);
            $sceneLight->Register();
            $sceneLight->SetHidden(true);
        }
    }

    //

    public function Name()
    {
        return $this->NameProp()->Value();
    }

    public function IsDefined()
    {
        return $this->Name() != "";
    }

    public function TurnOff()
    {
        $id = $this->SwitchIdProp()->Value();
        EIB_Switch(IPS_GetParent($id), false);
    }

    public function SaveToScene($sceneNumber)
    {
        $value = $this->DisplayVar()->GetValue();
        $this->SceneVars($sceneNumber)->SetValue($value);
    }

}

class DimLight extends Light {
    const Prefix = "DimLight";

    public function __construct($module, $index) {
        parent::__construct($module, $index, DimLight::Prefix);
    }

    // Properties

    private function SetValueIdProp()
    {        
        return new PropertyInteger($this->module, $this->prefix . $this->index . "SetValueId");
    }

    private function StatusValueIdProp()
    {        
        return new PropertyInteger($this->module, $this->prefix . $this->index . "StatusValueId");
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarIntegerNew($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars($sceneNumber)
    {
        return new IPSVarIntegerNew($this->module->InstanceId(), $this->SceneVarName());
    }

    // Backings

    public function DisplayVarBacking()
    {
        $getterId = $this->StatusValueIdProp()->Value();
        $setterId = $this->SetValueIdProp()->Value();
        $displayIdent = $this->DisplayVar()->Ident();
        return new Backing($this->module, $displayIdent, $getterId, $setterId, Backing::EIBTypeScale);
    }

    // Register

    public function RegisterProperties()
    {
        parent::RegisterProperties();

        $this->SetValueIdProp()->Register();
        $this->StatusValueIdProp()->Register();
    }

    public function RegisterVariables($sceneCount, $position)
    {
        parent::RegisterVariables($sceneCount, $position);

        $var = $this->DisplayVar();
        $var->SetProfile("~Intensity.100");

        $backing = $this->DisplayVarBacking();
        $backing->Update();
    }

    public function RegisterTriggers()
    {
        $backing = $this->DisplayVarBacking();
        $backing->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
    }

    //

    public function LoadFromScene($sceneNumber, $triggerIdent = "", $triggerValue = 0)
    {
        $value = $this->SceneVars($sceneNumber)->GetValue();

        if($this->IsDisplayVar($triggerIdent))
        {
            // load value stored in temp var
            $value = $triggerValue;
        }

        $this->DisplayVarBacking()->SetValue($value);
    }
}

class RGBLight extends Light {
    const Prefix = "RGBLight";

    public function __construct($module, $index) {
        parent::__construct($module, $index, RGBLight::Prefix);
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarIntegerNew($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars($sceneNumber)
    {
        return new IPSVarIntegerNew($this->module->InstanceId(), $this->SceneVarName());
    }

    // Properties
    private function SetValueIdProp()
    {        
        return new PropertyInteger($this->module, $this->prefix . $this->index . "SetValueId");
    }

    // Backings
    public function DisplayVarBacking()
    {
        $getterId = $this->SetValueIdProp()->Value();
        $setterId = $this->SetValueIdProp()->Value();
        $displayIdent = $this->DisplayVar()->Ident();
        return new Backing($this->module, $displayIdent, $getterId, $setterId, Backing::EIBTypeRGB);
    }

    // Register
    public function RegisterProperties()
    {
        parent::RegisterProperties();

        $this->SetValueIdProp()->Register();
    }

    public function RegisterVariables($sceneCount, $position)
    {
        parent::RegisterVariables($sceneCount, $position);

        $var = $this->DisplayVar();
        $var->SetProfile("~HexColor");

        $backing = $this->DisplayVarBacking();
        $backing->Update();
    }

    public function RegisterTriggers()
    {
        $backing = $this->DisplayVarBacking();
        $backing->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
    }

    //

    public function LoadFromScene($sceneNumber, $triggerIdent = "", $triggerValue = 0)
    {
        $value = $this->SceneVars($sceneNumber)->GetValue();

        if($this->IsDisplayVar($triggerIdent))
        {
            // load value stored in temp var
            $value = $triggerValue;
        }

        $this->DisplayVarBacking()->SetValue($value);
    }
}

class SwitchLight extends Light {
    const Prefix = "SwitchLight";

    public function __construct($module, $index) {
        parent::__construct($module, $index, SwitchLight::Prefix);
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarBooleanNew($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars($sceneNumber)
    {
        return new IPSVarBooleanNew($this->module->InstanceId(), $this->SceneVarName());
    }

    // Properties

    private function StatusIdProp()
    {        
        return new PropertyInteger($this->module, $this->prefix . $this->index . "StatusId");
    }

    // Backings

    public function DisplayVarBacking()
    {
        $getterId = $this->StatusIdProp()->Value();
        $setterId = $this->SwitchIdProp()->Value();
        $displayIdent = $this->DisplayVar()->Ident();
        return new Backing($this->module, $displayIdent, $getterId, $setterId, Backing::EIBTypeSwitch);
    }

    // Register

    public function RegisterProperties()
    {
        parent::RegisterProperties();

        $this->StatusIdProp()->Register();
    }

    public function RegisterVariables($sceneCount, $position)
    {
        parent::RegisterVariables($sceneCount, $position);

        $var = $this->DisplayVar();
        $var->SetProfile("~Switch");

        $backing = $this->DisplayVarBacking();
        $backing->Update();
    }

    public function RegisterTriggers()
    {
        $backing = $this->DisplayVarBacking();
        $backing->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
    }

    //

    public function LoadFromScene($sceneNumber, $triggerIdent = "", $triggerValue = 0)
    {
        $value = $this->SceneVars($sceneNumber)->GetValue();

        if($this->IsDisplayVar($triggerIdent))
        {
            // load value stored in temp var
            $value = $triggerValue;
        }

        $this->DisplayVarBacking()->SetValue($value);
    }
}

?>