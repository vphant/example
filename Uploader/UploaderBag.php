<?php

namespace App\Service\Uploader;

use App\Command\UploadCommand;

class UploaderBag
{
    /**
     * @var UploaderInterface[]
     */
    protected $uploaders = [];

    /**
     * @param LogoUploader $logoUploader
     * @param SliderUploader $sliderUploader
     */
    public function __construct(
        LogoUploader $logoUploader,
        SliderUploader $sliderUploader
    ) {
        $this->add(UploadCommand::DOMAIN_LOGO, $logoUploader);
        $this->add(UploadCommand::DOMAIN_SLIDER, $sliderUploader);
    }

    /**
     * @param string $alias
     * @return UploaderInterface
     * @throws \Exception
     */
    public function get(string $alias)
    {
        if (!isset($this->uploaders[$alias])) {
            throw new \Exception(
                sprintf('Unknown uploader "%s". Do you forget to register uploader in UploaderBag?', $alias)
            );
        }

        return $this->uploaders[$alias];
    }

    /**
     * @param string $alias
     * @param UploaderInterface $uploader
     */
    protected function add(string $alias, UploaderInterface $uploader)
    {
        $this->uploaders[$alias] = $uploader;
    }

}
