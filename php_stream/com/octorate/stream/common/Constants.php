<?php

namespace com\octorate\stream\common;

class Constants
{

    /**
     * @return string
     */
    public static function getOctorateApi()
    {
        if (gethostname() == 'octorate-23456-Z16-fddddd') {
            // would not work outside server for some path
            return "http://localhost:8080/connect";
        }
        if (gethostname() == 'octocloud') {
            return "https://cloud.octorate.com/connect";
        }
        // !! localhost do not have the websocket open
        return "https://api.octorate.com/connect";

    }

    /**
     * @return string client id of octorate api, used normally for frontend
     */
    public static function getApiOctorateService()
    {
        // return "public_46deaa61a23f4e2284c428abbb569da4";
        return "public_4CVBNMBB089049f75467856432eab2aa";
    }

    /**
     * @return string secret key, normally used for frontend
     */
    public static function getSecretOctorateService()
    {
        return "secret_e2RTYUUYTREWb39240DFGHJKLJHGFDSAHLIYUM";
    }


}