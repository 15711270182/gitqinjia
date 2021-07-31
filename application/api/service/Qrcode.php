<?php


namespace app\api\service;


class Qrcode
{
    /**
     * 获取小程序配置
     * @return array
     */
    private function config()
    {
        return config('wechat.miniapp');
    }

    /**
     * @Notes:生成二维码
     * @Interface generateQrCode
     * @author: zy
     * @Time: 2020/11/6   9:24
     */
    public function generateQrCode($path,$sid,$page_path = 'pages/home/home',$weight='100'){

        $image_content = \We::WeMiniQrcode($this->config())->createMiniScene('source='.$sid,$page_path,$weight);

        $file_name = time().createRandStr(7).'_'.$sid;
        $q_path  = createFile($path,$file_name,$image_content);
        return $q_path;
    }
}