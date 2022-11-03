<?
declare(strict_types=1);

require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class ShutterControlArray {
    private int $size;
    private BetterBase $module;

    public function __construct(BetterBase $module, int $size)
    {
        $this->module = $module;
        $this->size = $size;
    }

    public function Count()
    {
        $count = 0;
        
        for($i=0; $i<$this->size; $i++)
        {
            $obj = $this->At($i);

            if(!$obj->IsDefined())
            {
                return $count;
            }

            $count++;
        }

        return $count;
    }

    public function At(int $index)
    {
        return new ShutterControl($this->module, $index);
    }

    public function RegisterProperties()
    {
        for($i=0; $i<$this->size; $i++)
        {
            $this->At($i)->RegisterProperties();
        }
    }

    public function RegisterTriggers()
    {
        for($i=0; $i<$this->Count(); $i++)
        {
            $this->At($i)->RegisterTriggers();
        }
    }
}

class ShutterControl
{
    const StrPrefix = "ShutterControl";

    private int $index;
    private BetterBase $module;

    public function __construct(BetterBase $module, int $index) {
        $this->module = $module;
        $this->index = $index;
    }

  // Properties

  private function SetPositionIdProp()
  {
    return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "SetPositionIdProp");
  }   

    private function UpDownIdProp()
    {        
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "UpDownIdProp");
    }   

    private function StopIdProp()
    {        
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "StopIdProp");
    }

  // Events

  private function SetPositionTrigger()
  {
    return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "SetPositionTrigger");
  }

    private function UpDownTrigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "UpDownTrigger");
    }

    private function StopTrigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "StopTrigger");
    }

    public function RegisterProperties()
    {
    /*if ($this->SetPositionId() != 0) */ $this->SetPositionIdProp()->Register();
    /*if ($this->UpDownId() != 0) */
    $this->UpDownIdProp()->Register();
    /*if ($this->StopId() != 0) */
    $this->StopIdProp()->Register();
    }

    public function RegisterTriggers()
    {
    if ($this->SetPositionId() != 0) $this->SetPositionTrigger()->Register("", $this->SetPositionId(), 'BSN_SetPositionEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
    if ($this->UpDownId() != 0) $this->UpDownTrigger()->Register("", $this->UpDownId(), 'BSN_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);
    if ($this->StopId() != 0) $this->StopTrigger()->Register("", $this->StopId(), 'BSN_StopEvent($_IPS[\'TARGET\']);', IPSEventTrigger::TypeUpdate);
  }

  public function SetPositionId()
  {
    return $this->SetPositionIdProp()->Value();
  }

    public function UpDownId()
    {
        return $this->UpDownIdProp()->Value();
    }

    public function StopId()
    {
        return $this->StopIdProp()->Value();
    }
    
    public function IsDefined()
    {
        return $this->UpDownId() != 0;
    }
}

?>