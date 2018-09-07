<?php

namespace App\Service;

use Twig_Environment;

class Mailer
{

    /**
     * @var \Swift_Mailer $mailer
     */
    protected $swiftMailer;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $params;

    /**
     * @param \Swift_Mailer $swiftMailer
     * @param Twig_Environment $twig
     * @param array $params
     */
    public function __construct(\Swift_Mailer $swiftMailer, Twig_Environment $twig, $params)
    {
        $this->swiftMailer = $swiftMailer;
        $this->twig = $twig;
        $this->params = $params;
    }

    /**
     * @param $to
     * @param $hash
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendTestEmail($to, $hash)
    {
        $body = $this->twig->render('emails/test.html.twig', ['hash' => $hash]);

        $this->sendEmail(
            'Test email from site. '. $hash,
            $to,
            $body
        );
    }

    /**
     * @param $url
     * @param $to
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendEmailVerification($url, $to)
    {
        $body = $this->twig->render('emails/registration.html.twig', ['url' => $url]);

        $this->sendEmail('Site email verification', $to, $body);
    }

    /**
     * @param $url
     * @param $to
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendPasswordReset($url, $to)
    {
        $body = $this->twig->render('emails/reset_password.html.twig', ['url' => $url]);

        $this->sendEmail('Site reset password', $to, $body);
    }

    /**
     * @param string $subject
     * @param string $to
     * @param string $body
     */
    protected function sendEmail($subject, $to, $body)
    {
        $message = new \Swift_Message();
        $message
            ->setSubject($subject)
            ->setFrom($this->params['from'])
            ->setTo($to)
            ->setBody($body, 'text/html');

        $this->swiftMailer->send($message);
    }

}
