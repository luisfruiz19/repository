<?php

namespace App\Core;

class ResponseUtil
{
    /**
     * @param string $message
     * @param mixed  $data
     *
     * @return array
     */
    public static function makeResponse($message, $data)
    {
        return [
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ];
    }

    /**
     * @param string $message
     * @param array  $data
     *
     * @return array
     */
    public static function makeError($message, array $data = [])
    {
        $res = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($data)) {
            $res['data'] = $data;
        }

        return $res;
    }


    public static function dataTableResponse($data,$total,$message,$filters=[]){
        return array_merge([
            'success' => true,
            'data'=>$data,
            'total'=>$total,
            'total_view'=>count($data),
            'message'=>$message
        ],$filters);
    }
}
