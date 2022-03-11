<?php

namespace MadeHQ\Cloudinary\UserForms;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use MadeHQ\Cloudinary\UserForms\Controllers\FormAdmin;
use SilverStripe\Forms\TextField;
use SilverStripe\UserForms\Model\EditableFormField\EditableFileField as EditableFormFieldEditableFileField;

/**
 * @param string $UploadFolder
 */
class EditableFileField extends EditableFormFieldEditableFileField
{
    private static $table_name = 'CloudinaryEditableFileField';

    private static $upload_prefix = '';

    /**
     *
     */
    private static $hidden = false;

    private static $db = [
        'UploadFolder' => 'Varchar(255)',
    ];

    /**
     * @inheritdoc
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('FolderID', static::getUploadFolderField());
        return $fields;
    }

    public static function getUploadFolderField()
    {
        return TextField::create('UploadFolder')
            ->setDescription(static::getUploadPrefixDescription())
            ->setAttribute('placeholder', FormAdmin::getDefaultSubmissionFolder());
    }

    /**
     * @return string
     */
    public static function getUploadPrefixDescription()
    {
        $prefix = trim(static::config()->get('upload_prefix'), '/');

        return ($prefix) ?
            _t(__CLASS__ . '.UPLOAD_PREFIX_DESCRIPTION', 'Prefix ({prefix})', [
                'prefix' => $prefix,
            ]) :
            '';
    }

    /**
     * Stores the file in Cloudinary and stores it
     *
     * @return string
     */
    public function getValueFromData()
    {
        $data = func_get_arg(0);
        $uploadDir = ltrim(static::config()->get('upload_prefix'), '/');
        if ($uploadFolder = ltrim($this->UploadFolder)) {
            $uploadDir.= $uploadFolder;
        }

        if (
            array_key_exists($this->Name, $data) &&
            is_array($data[$this->Name])
        ) {
            $fileName = sprintf(
                'form-%d_field-%d/%s',
                $this->Parent()->ID,
                $this->ID,
                $data[$this->Name]['name']
            );

            $api = (new Cloudinary(Configuration::instance()))->uploadApi();
            $config = [
                'folder' => $uploadDir,
                'public_id' => $fileName,
                'resource_type' => 'raw',
                'type' => 'private',
            ];

            $newData = $api->upload(
                $data[$this->Name]['tmp_name'],
                $config
            );

            return $newData['public_id'];
        }

        return null;
    }
}

