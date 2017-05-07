<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\helpers\Html;
use GuzzleHttp\Client; // подключаем Guzzle
use yii\helpers\Url;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionSay($message = 'Привет')
    {
        return $this->render('say', ['message' => $message]);
    }

    public function actionParserResult()
    {
        $client = new Client();

        $res = $client->request('GET', 'https://www.kinopoisk.ru/afisha/city/5010/');

        // получаем данные между открывающим и закрывающим тегами body

        $body = $res->getBody();

        $document = \phpQuery::newDocumentHTML($body);

        $content = $document->find('.films_metro');

        foreach ($content as $elem) {
            //pq аналог $ в jQuery
            $pq = pq($elem);

            // удаляем лишние элементы
            $pq->find('img')->remove();

            $pq->find('.minus')->remove();

            $pq->find('.film_info')->remove();

            $pq->find('p');
        }
        //преобразовываем html сущности
        $contentHtml = html_entity_decode($content);
        //конвентируем результат под кодировку windows-1252
        $contentEncoding = mb_convert_encoding($contentHtml, 'windows-1252', mb_detect_encoding($contentHtml));

        $result = ' <meta http-equiv="Content-Type" content="text/html; charset=windows-1251" /> ' . $contentEncoding;
        //записываем файл, который содержит отфильтрованный и читабельный результат
        file_put_contents( 'result.html', $result, LOCK_EX);
        //получаем данные из файла
        $fileContent = file_get_contents('result.html', FILE_USE_INCLUDE_PATH);

        return $this->render('parserResult', ['body' => $fileContent]);
    }
}
