<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Backing.php");

require_once(__DIR__ . "/../IPS/IPS.php");

class LightArray {
    const TypeSwitch = 0;
    const TypeDim = 1;
    const TypeRGB = 2;

    private int $size;
    private int $type;
    private BetterBase $module;

    public function __construct(BetterBase $module, int $size, int $type)
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

    public function At(int $index)
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
                IPS_LogMessage("BL", "LightArray::At - Unsupported type: $this->type");
        }
    }

    public function GetLightForDisplayIdent(string $ident)
    {
        for($i = 0; $i<$this->size; $i++)
        {
            $var = $this->At($i);
            if($var->IsDisplayVar($ident))
                return $var;
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

    public function RegisterVariables(int $sceneCount, int $position)
    {
        IPS_LogMessage("BL", "RegisterVariablessss");

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

    public function SaveToScene(int $sceneNumber)
    {
        for($i = 0; $i < $this->Count(); $i++)
        {
            $this->At($i)->SaveToScene($sceneNumber);
        }
    }

    public function LoadFromScene(int $sceneNumber, string $triggerIdent, bool $triggerBoolValue)
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

    protected int $index;
    protected BetterBase $module;
    protected string $prefix;

    public function __construct(BetterBase $module, int $index, string $prefix) {
        $this->module = $module;
        $this->index = $index;
        $this->prefix = $prefix;
    }

    // Properties

    protected function NameProp()
    {        
        return new IPSPropertyString($this->module, $this->prefix . $this->index . "Name");
    }   

    protected function SwitchIdProp()
    {        
        return new IPSPropertyInteger($this->module, $this->prefix . $this->index . "SwitchId");
    }

    // Variables

    protected abstract function SceneVars(int $sceneNumber);
    protected abstract function DisplayVarProfile();
    protected abstract function DisplayVar();

    protected function DisplayVarName()
    {
        return $this->prefix . $this->index . "DisplayVar";
    }

    public function IsDisplayVar(string $ident)
    {
        return $ident == $this->DisplayVar()->Ident();
    }

    protected function SceneVarName(int $sceneNumber)
    {
        return BetterBase::PersistentPrefix . 
            $this->prefix . $this->index . 
            self::StrScene . $sceneNumber . 
            "Value";
    }

    // Register

    protected function _RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->SwitchIdProp()->Register();
    }

    private function RegisterDisplayVar(int $position)
    {
        $name = $this->Name();
        $var = $this->DisplayVar();
        $var->Register($name, "", $position);
        $this->module->EnableAction($var->Ident());
    }

    private function RegisterSceneVars(int $sceneCount)
    {
        for($i = 0; $i<$sceneCount; $i++)
        {
            $sceneLight = $this->SceneVars($i);
            $sceneLight->Register();
            $sceneLight->SetHidden(true);
        }
    }

    public function RegisterVariables(int $sceneCount, int $position)
    {
        $name = $this->Name();
        $this->RegisterDisplayVar($position);
        $this->RegisterSceneVars($sceneCount);

        $var = $this->DisplayVar();
        $var->SetProfile($this->DisplayVarProfile());

        $backing = $this->DisplayVarBacking();
        $backing->Update();
    }

    public function RegisterTriggers()
    {
        $backing = $this->DisplayVarBacking();
        $backing->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
    }

    public abstract function RegisterProperties();

    //

    public abstract function DisplayVarBacking();

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

    public function SaveToScene(int $sceneNumber)
    {
        $value = $this->DisplayVar()->Value();
        $this->SceneVars($sceneNumber)->SetValue($value);
    }

    public function LoadFromScene(int $sceneNumber, string $triggerIdent = "", int $triggerValue = 0)
    {
        $value = $this->SceneVars($sceneNumber)->Value();

        if($this->IsDisplayVar($triggerIdent))
        {
            // load value stored in temp var
            $value = $triggerValue;
        }

        $this->DisplayVarBacking()->SetValue($value);
    }
}

class DimLight extends Light {
    const Prefix = "DimLight";

    public function __construct($module, int $index) {
        parent::__construct($module, $index, DimLight::Prefix);
    }

    protected function DisplayVarProfile()
    {
        return "~Intensity.100";
    }

    // Properties
    private function SetValueIdProp()
    {        
        return new IPSPropertyInteger($this->module, $this->prefix . $this->index . "SetValueId");
    }

    private function StatusValueIdProp()
    {        
        return new IPSPropertyInteger($this->module, $this->prefix . $this->index . "StatusValueId");
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarInteger($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars(int $sceneNumber)
    {
        return new IPSVarInteger($this->module->InstanceId(), $this->SceneVarName($sceneNumber));
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
        $this->_RegisterProperties();

        $this->SetValueIdProp()->Register();
        $this->StatusValueIdProp()->Register();
    }
}

class RGBLight extends Light {
    const Prefix = "RGBLight";

    public function __construct($module, int $index) {
        parent::__construct($module, $index, RGBLight::Prefix);
    }

    protected function DisplayVarProfile()
    {
        return "~HexColor";
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarInteger($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars(int $sceneNumber)
    {
        return new IPSVarInteger($this->module->InstanceId(), $this->SceneVarName($sceneNumber));
    }

    // Properties
    private function SetValueIdProp()
    {        
        return new IPSPropertyInteger($this->module, $this->prefix . $this->index . "SetValueId");
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
        $this->_RegisterProperties();

        $this->SetValueIdProp()->Register();
    }
}

class SwitchLight extends Light {
    const Prefix = "SwitchLight";

    public function __construct($module, int $index) {
        parent::__construct($module, $index, SwitchLight::Prefix);
    }

    protected function DisplayVarProfile()
    {
        return "~Switch";
    }

    // Variables
    protected function DisplayVar()
    {
        return new IPSVarBoolean($this->module->InstanceId(), $this->DisplayVarName());
    }

    protected function SceneVars(int $sceneNumber)
    {
        return new IPSVarBoolean($this->module->InstanceId(), $this->SceneVarName($sceneNumber));
    }

    // Properties

    private function StatusIdProp()
    {        
        return new IPSPropertyInteger($this->module, $this->prefix . $this->index . "StatusId");
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
        $this->_RegisterProperties();

        $this->StatusIdProp()->Register();
    }
}

?>