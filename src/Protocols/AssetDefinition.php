<?php

namespace OKohei\OpenAssets\Protocols;


class AssetDefinition 
{
    public $assetDefinitionUrl;
    public $assetIds;
    public $nameShort;
    public $name;
    public $contractUrl;
    public $issuer;
    public $description;
    public $descriptionMime;
    public $type;
    public $divisibility;
    public $linkToWebsite;
    public $iconUrl;
    public $imageUrl;
    public $version;
    public $proofOfAuthenticity;

    public function __construct()
    {
        $this->assetDefinitionUrl = null;
        $this->assetIds = [];
        $this->nameShort = null;
        $this->name = null;
        $this->contractUrl = null;
        $this->issuer = null;
        $this->description = null;
        $this->descriptionMime = null;
        $this->type = null;
        $this->divisibility = 0;
        $this->linkToWebsite = null;
        $this->iconUrl = null;
        $this->imageUrl = null;
        $this->version = '1.0';
        $this->proofOfAuthenticity = false;
    }

    public static function parseJson($json)
    {
        $data = json_decode($json);
        $assetDefinition = new AssetDefinition();
        $assetDefinition->assetIds = $data->asset_ids;
        $assetDefinition->nameShort = $data->name_short;
        $assetDefinition->name = $data->name;
        $assetDefinition->contractUrl = isset($data->contract_url) ? $data->contract_url : null ;
        $assetDefinition->issuer = isset($data->issuer) ? $data->issuer : null;
        $assetDefinition->description = isset($data->description) ? $data->description: null;
        $assetDefinition->descriptionMime = isset($data->description_mime) ? $data->description_mime : null;
        $assetDefinition->type = isset($data->type) ? $data->type : null;
        $assetDefinition->divisibility = isset($data->divisibility) ? $data->divisibility : null ;
        $assetDefinition->linkToWebsite = isset($data->link_to_website) ? $data->link_to_website  : null;
        $assetDefinition->iconUrl = isset($data->icon_url) ? $data->icon_url : null;
        $assetDefinition->imageUrl = isset($data->image_url) ? $data->image_url : null;
        $assetDefinition->version = isset($data->version) ? $data->version : null;
        return $assetDefinition;
    }
    
    public function hasAssetId($assetId)
    {
        if ($this->assetIds == null || empty($this->assetIds)) {
            return false;
        }
        return in_array($assetId, $this->assetIds);    
    }

    public function toJson()
    {
        unset($this->proofOfAuthenticity);
        return json_encode($this, true);
    }

    public function getProofOfAuthenticity()
    {
        $this->proofOfAuthenticity = $this->checkAuthenticity();
    }

    private function checkAuthenticity()
    {
        if (!is_null($this->assetDefinitionUrl) && $this->linkToWebsite) {
            $subject = $this->getSslCertificate();
            return ($subject && $subject == $this->issuer);
        }
        return false;
    }

    private function getSslCertificate()
    {
        return true;
    }
}
