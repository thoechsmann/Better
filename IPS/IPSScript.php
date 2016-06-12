<?

require_once(__DIR__ . "/IPSObject.php");

class IPSScriptNew extends IPSObjectNew
{
    public function Register($name, $content = "<?\n?>", $position = 0)    
    {        
        $id = $this->_Register($name, $position);
            
        IPS_SetScriptContent($id, $content);
        
        return $id;        
    }

    public function SetContent($content)
    {
        IPS_SetScriptContent($this->Id(), $content);
    }

    protected function CreateObject()
    {
        IPS_CreateScript(0);
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteScript($id);
    }
}


?>