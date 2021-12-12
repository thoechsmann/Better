<?
abstract class IPSProperty
{
    protected $name;
    protected $module;
    protected $caption;

    public function __construct($module, $name, $caption = "")
    {
        $this->module = $module;
        $this->name = $name;
        $this->caption = $caption;
    }

    public function Name()
    {
        return $this->name;
    }

    public function SetValue($value)
    {
        IPS_SetProperty($this->module->InstanceId(), $this->name, $value);
    }

    public function GetConfigurationFormEntry()
    {
        return "{ \"type\": \"SelectVariable\",
                  \"width\": \"100%\",
                  \"name\": \"". $this->name . "\",
                  \"caption\": \"" . $this->caption . "\" }";
    }

    abstract public function Value();
    abstract public function Register($value);
}

class IPSPropertyInteger extends IPSProperty
{
    public function Register($value = 0)
    {
        // IPS_LogMessage("PropertyInteger", "Registering property: " . $this->name . " = " . $value);
        $this->module->RegisterPropertyInteger($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyInteger($this->name);
    }
}

class IPSPropertyString extends IPSProperty
{

    public function Register($value = "")
    {
        // IPS_LogMessage("PropertyString", "Registering property: " . $this->name . " = " . $value);
        $this->module->RegisterPropertyString($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyString($this->name);
    }
}

abstract class IPSPropertyArray
{
    protected $module;
    protected $count;
    protected $caption;
    protected $properties = array();

    public function __construct($module, $name, $count, $caption="")
    {
        $this->module = $module;
        $this->name = $name;
        $this->count = $count;
        $this->caption = $caption;

        for ($i = 0; $i<$count; $i++) {
            $this->properties[$i] = $this->CreateProperty($name . $i, $caption . " " . ($i+1));
        }
    }

    public function Count()
    {
        return $this->count;
    }

    public function RegisterAll()
    {
        for ($i = 0; $i<$this->count; $i++) {
            $this->properties[$i]->Register();
        }
    }

    public function GetAllConfigurationFormEntries()
    {
        $retArray = array();
        for ($i = 0; $i<$this->count; $i++) {
            $retArray[$i] = $this->properties[$i]->GetConfigurationFormEntry();
        }

        return $retArray;
    }

    public function At($index)
    {
        return $this->properties[$index];
    }

    public function ValueAt($index)
    {
        return $this->properties[$index]->Value();
    }

    public function NameAt($index)
    {
        return $this->properties[$index]->Name();
    }

    abstract protected function CreateProperty($name, $caption);
}

class IPSPropertyArrayInteger extends IPSPropertyArray
{
    protected function CreateProperty($name, $caption="")
    {
        return new IPSPropertyInteger($this->module, $name, $caption);
    }
}

class IPSPropertyArrayString extends IPSPropertyArray
{
    protected function CreateProperty($name, $caption="")
    {
        return new IPSPropertyString($this->module, $name, $caption);
    }
}
