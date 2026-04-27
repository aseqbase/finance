<?php
namespace MiMFa\Library;

use DateTime;
use MiMFa\Library\Convert;
use MiMFa\Library\DataTable;
use MiMFa\Library\Struct;

class MetaDataTable extends DataTable
{
    public function __construct(\MiMFa\Library\DataBase|null $dataBase, $name, $prefix = null, $nameConvertors = null)
    {
        parent::__construct($dataBase??\_::$Back->DataBase, $name, $prefix??\_::$Back->DataBasePrefix,$nameConvertors??\_::$Back->DataTableNameConvertors);
    }


    public function Data($idOrInstance)
    {
        return is_array($idOrInstance) ? $idOrInstance : parent::Get($idOrInstance);
    }
    public function MetaData($idOrInstance, $metaData = null)
    {
        $metaData = $metaData ?: parent::GetMetaData(is_array($idOrInstance) ? $idOrInstance["Id"] : $idOrInstance, []);
        if (!is_array($metaData))
            return Convert::FromJson($metaData) ?? [];
        return $metaData;
    }

    /**
     * To Check if the Instance is Branched of another Instance
     * @param mixed $idOrInstance
     */
    public function IsRoot($idOrInstance)
    {
        return get($this->Data($idOrInstance), "ParentId") ? false : true;
    }
    /**
     * To get the Instance parent
     * @param mixed $idOrInstance
     */
    public function GetParent($idOrInstance)
    {
        $r = get($this->Data($idOrInstance), "ParentId");
        if ($r)
            return $this->Data($r)?:null;
        else
            return null;
    }
    /**
     * Get all parents recursively
     * @param mixed $idOrInstance
     */
    public function GetAncestors($idOrInstance)
    {
        while ($idOrInstance = $this->GetParent($idOrInstance))
            yield $idOrInstance;
    }
    /**
     * Get all branches
     * @param mixed $idOrInstance
     */
    public function GetChildren($idOrInstance)
    {
        $idOrInstance = is_array($idOrInstance) ? $idOrInstance["Id"] : $idOrInstance;
        return $this->Select("*", "ParentId=:Id", [":Id" => $idOrInstance]);
    }
    /**
     * Get all branches recursively
     * @param mixed $idOrInstance
     */
    public function GetDescendants($idOrInstance)
    {
        yield $idOrInstance;
        foreach ($this->GetChildren($idOrInstance) as $branch)
            yield from $this->GetDescendants($branch);
    }


    public function Seen($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return $this->SetMetaValue($idOrInstance["Id"], "Seen", 1);
    }
    public function IsSeen($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return get($metaData, "Seen");
    }

    public function AddToMetaData($idOrInstance, $metaData = null, $key = null, $value = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        if (is_array($metaData))
            $metaData[$key] = $value;
        else
            $metaData = is_null($key)?[$value]:[$key => $value];
        $metaData["Seen"] = false;
        return $metaData;
    }
    public function RemoveFromMetaData($idOrInstance, $metaData = null, $key = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        unset($metaData[$key]);
        return $metaData;
    }

    public function GenerateProcedure($status, $data)
    {
        return [
            "User" => \_::$User->Name,
            "UserId" => \_::$User->Id,
            "Status" => $status,
            "Data" => $data,
            "Timestamp" => Convert::ToDateTime()->getTimestamp(),
        ];
    }
    public function AddProcedure($idOrInstance, &$metaData = null, $status = null, $data = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        $item = $this->GenerateProcedure($status, $data);
        if (isset($metaData["Procedure"]))
            $metaData["Procedure"][] = $item;
        else
            $metaData["Procedure"] = [$item];
        return $item;
    }
    public function RemoveProcedure($idOrInstance, &$metaData = null, $key = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        if (!isset($metaData["Procedure"]))
            return false;
        if (is_null($key))
            unset($metaData["Procedure"]);
        else {
            if (!isset($metaData["Procedure"][$key]))
                return false;
            unset($metaData["Procedure"][$key]);
        }
        return true;
    }
    public function GetCurrentProcedure($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return last(
            $this->GetProcedure($idOrInstance, $metaData)
        );
    }
    public function GetProcedureTable($idOrInstance, $metaData = null)
    {
        $table = [["#", "User", "Status", "Description", "Time"]];
        $procedure = $this->GetProcedure($idOrInstance, $metaData);
        if (!$procedure)
            return $table;
        $c = count($procedure);
        foreach (array_reverse($procedure) as $key => $value)
            $table[] = [
                $c - $key,
                Struct::Link($value["User"] ?? "User", \_::$Address->UserRootUrlPath . $value["UserId"]),
                $value["Status"],
                Struct::Span(Convert::ToExcerpt($value["Data"], 0, 30), null, ["tooltip" => $value["Data"]]),
                Convert::ToShownDateTimeString((new DateTime())->setTimestamp($value["Timestamp"]))
            ];
        return $table;
    }
    public function GetProcedure($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return get(
            $metaData,
            "Procedure"
        );
    }
    public function SetProcedure($idOrInstance, $metaData = null, $procedure = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        if ($procedure)
            $metaData["Procedure"] = $procedure;
        return $this->SetMetaData($idOrInstance["Id"], $metaData);
    }

    public function AddTrack($idOrInstance, &$metaData = null, $track = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        if (isset($metaData["Tracks"])) {
            if (!in_array($track, $metaData["Tracks"]))
                $metaData["Tracks"][] = $track;
            else
                $track = null;
        } else
            $metaData["Tracks"] = [$track];
        return $track;
    }
    public function RemoveTrack($idOrInstance, &$metaData = null, $key = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        if (!isset($metaData["Tracks"]))
            return false;
        if (is_null($key))
            unset($metaData["Tracks"]);
        else {
            if (!isset($metaData["Tracks"][$key]))
                return false;
            unset($metaData["Tracks"][$key]);
        }
        return true;
    }
    public function GetCurrentTrack($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return last(
            $this->GetTracks($idOrInstance, $metaData)
        );
    }
    public function GetTracks($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return get(
            $metaData,
            "Tracks"
        );
    }
    public function SetTracks($idOrInstance, $metaData = null, $tracks = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        if ($tracks)
            $metaData["Tracks"] = $tracks;
        return $this->SetMetaData($idOrInstance["Id"], $metaData);
    }


    public function GenerateMessage($title, $content)
    {
        return [
            "User" => \_::$User->Name,
            "UserId" => \_::$User->Id,
            "Title" => $title,
            "Content" => $content,
            "Timestamp" => Convert::ToDateTime()->getTimestamp(),
        ];
    }
    public function AddMessage($idOrInstance, &$metaData = null, $title = null, $content = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        $item = $this->GenerateMessage($title, $content);
        if (isset($metaData["Messages"]))
            $metaData["Messages"][] = $item;
        else
            $metaData["Messages"] = [$item];
        return $item;
    }
    public function RemoveMessage($idOrInstance, &$metaData = null, $key = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        if (!isset($metaData["Messages"]))
            return false;
        if (is_null($key))
            unset($metaData["Messages"]);
        else {
            if (!isset($metaData["Messages"][$key]))
                return false;
            unset($metaData["Messages"][$key]);
        }
        return true;
    }
    public function GetCurrentMessage($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return last(
            $this->GetMessages($idOrInstance, $metaData)
        );
    }
    public function GetMessagesTable($idOrInstance, $metaData = null)
    {
        $items = $this->GetMessages($idOrInstance, $metaData);
        if (!$items)
            return null;
        $c = count($items);
        $table = [["#", "User", "Title", "Content", "Time"]];
        foreach (array_reverse($items) as $key => $value)
            $table[] = [
                $c - $key,
                Struct::Link($value["User"] ?? "User", \_::$Address->UserRootUrlPath . $value["UserId"]),
                $value["Title"],
                Struct::Span(Convert::ToExcerpt($value["Content"], 0, 30), null, ["tooltip" => $value["Content"]]),
                Convert::ToShownDateTimeString((new DateTime())->setTimestamp($value["Timestamp"]))
            ];
        return $table;
    }
    public function GetMessages($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return get(
            $metaData,
            "Messages"
        ) ?? [];
    }
    public function SetMessages($idOrInstance, $metaData = null, $message = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        if ($message)
            $this->AddMessage($idOrInstance, $metaData, get($message, "Title"), get($message, "Content"));
        return $this->SetMetaData($idOrInstance["Id"], $metaData);
    }

    public function GenerateAttachment($path, $title, $content)
    {
        return [
            "User" => \_::$User->Name,
            "UserId" => \_::$User->Id,
            "Title" => $title,
            "Content" => $content,
            "Path" => $path,
            "Timestamp" => Convert::ToDateTime()->getTimestamp(),
        ];
    }
    public function AddAttachment($idOrInstance, &$metaData = null, $path = null, $title = null, $content = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        $metaData["Seen"] = false;
        $item = $this->GenerateAttachment($path, $title, $content);
        if (isset($metaData["Attachments"]))
            $metaData["Attachments"][] = $item;
        else
            $metaData["Attachments"] = [$item];
        return $item;
    }
    public function RemoveAttachment($idOrInstance, &$metaData = null, $key = null)
    {
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $metaData["Seen"] = false;
        if (!isset($metaData["Attachments"]))
            return false;
        if (is_null($key))
            unset($metaData["Attachments"]);
        else {
            if (!isset($metaData["Attachments"][$key]))
                return false;
            unset($metaData["Attachments"][$key]);
        }
        return true;
    }
    public function GetCurrentAttachment($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return last(
            $this->GetAttachments($idOrInstance, $metaData)
        );
    }
    public function GetAttachmentsTable($idOrInstance, $metaData = null)
    {
        $items = $this->GetAttachments($idOrInstance, $metaData);
        if (!$items)
            return null;
        $c = count($items);
        $table = [["#", "User", "Title", "Description", "Time"]];
        foreach (array_reverse($items) as $key => $value)
            $table[] = [
                $c - $key,
                Struct::Link($value["User"] ?? "User", \_::$Address->UserRootUrlPath . $value["UserId"]),
                Struct::Bold(Convert::ToExcerpt($value["Title"], 0, 30), $value["Path"], ["Target" => "_blank"]),
                $value["Content"] ? ($r = isUrl($value["Content"]) ?
                    Struct::Span(Convert::ToExcerpt($value["Content"], 0, 30, reverse: true), $value["Content"], ["tooltip" => $value["Content"]]) :
                    Struct::Icon("eye", Convert::ToScript(function ($data) {
                        deliverModal(\MiMFa\Library\Struct::Convert($data));
                    }, $value["Content"]))) : null,
                Convert::ToShownDateTimeString((new DateTime())->setTimestamp($value["Timestamp"]))
            ];
        return $table;
    }
    public function GetAttachments($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        return get(
            $metaData,
            "Attachments"
        ) ?? [];
    }
    public function SetAttachments($idOrInstance, $metaData = null, $message = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        if ($message)
            $this->AddAttachment($idOrInstance, $metaData, get($message, "Path"), get($message, "Title"), get($message, "Content"));
        return $this->SetMetaData($idOrInstance["Id"], $metaData);
    }
}