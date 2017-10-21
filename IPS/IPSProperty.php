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
    protected $properties = array();

    public function __construct($module, $name, $count)
    {
        $this->module = $module;
        $this->name = $name;
        $this->count = $count;

        for ($i = 0; $i<$count; $i++) {
            $this->properties[$i] = static::CreateProperty($name . $i);
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

    abstract protected function CreateProperty($name);
}

class IPSPropertyArrayInteger extends IPSPropertyArray
{
    protected function CreateProperty($name)
    {
        return new IPSPropertyInteger($this->module, $name);
    }
}

class IPSPropertyArrayString extends IPSPropertyArray
{
    protected function CreateProperty($name)
    {
        return new IPSPropertyString($this->module, $name);
    }
}
