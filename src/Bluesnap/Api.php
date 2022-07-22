<?php

namespace Bluesnap;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use Bluesnap\Exceptions\MissingFieldsException;

class Api
{
    private static function getClient()
    {
        $credentials = Bluesnap::getCredentials();
        $base_url = Bluesnap::getBaseUrl();

        if (!$credentials)
        {
            throw new MissingFieldsException('No BlueSnap credentials provided');
        }

        return new Client([
            'base_uri' => $base_url,
//            'timeout'  => 10.0,
            'auth' => $credentials,
            'bluesnap-version' => '3.0',
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);
    }

    public static function get($endpoint, $id = null, $query_params = null)
    {
        $client = self::getClient();

        $args = null;
        if ($query_params && is_array($query_params)) {
            $args = [ 'query' => $query_params ];
        }

        $id_string = $id ? '/'. $id : '';
        $response = $client->get($endpoint . $id_string, $args ?? []);

        if ($response->getStatusCode() === 200)
        {
            $data = json_decode($response->getBody()->getContents());

            return $data;
        }

        return null;
    }

    public static function post($endpoint, $data, $id_in_header)
    {
        $client = self::getClient();

    //    $clientHandler = $client->getConfig('handler');
    //    $tapMiddleware = Middleware::tap(function ($request) {
    //        echo "Request data:\n\ncontent-type: ". $request->getHeaderLine('Content-Type') ."\n";
    //        echo "body: ". $request->getBody() ."\n\nResponse data:\n\n";
    //    });

        $response = $client->post($endpoint, [
            'json' => $data,
        //    'handler' => $tapMiddleware($clientHandler)
        ]);

        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201)
        {
            if ($id_in_header && $response->hasHeader('Location')) {
                $location = $response->getHeader('Location');
                $location_array = explode('/', $location[0]);

                // If we're fetching a Hosted Payment Fields token, return that.
                if ($endpoint == 'payment-fields-tokens') {
                  return ['hosted_payment_fields_token' => $location_array[6]];
                }

                $model_id = end($location_array);
                return [ 'id' => (int) $model_id ];
            } else {
                $model = $response->getBody()->getContents();
                return json_decode($model, true);
            }
        }

        return $response;
    }

    public static function put($endpoint, $data = null, $query_params = null)
    {
        $client = self::getClient();

//        $clientHandler = $this->client->getConfig('handler');
//        $tapMiddleware = Middleware::tap(function ($request) {
//            echo "Request data:\n\ncontent-type: ". $request->getHeaderLine('Content-Type') ."\n";
//            echo "body: ". $request->getBody() ."\n\nResponse data:\n\n";
//        });

        $options = [
            'json' => $data,
//            'handler' => $tapMiddleware($clientHandler)
        ];
        if ($query_params && is_array($query_params)) {
            $options['query'] = $query_params;
        }

        $response = $client->put($endpoint, $options);

        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 204)
        {
            return json_decode($response->getBody()->getContents());
        }

        return $response;
    }

    public static function delete($endpoint)
    {
        $client = self::getClient();

        $response = $client->delete($endpoint);

        if ($response->getStatusCode() === 204)
        {
            return [];
        }

        return $response;
    }
}
