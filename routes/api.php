<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/json', function() {
    $client = new Client([
        'timeout' => 10,
        'verify'  => false,
    ]);
    $site = 'https://ru.tradingeconomics.com/country-list/population';
    $response = $client->get($site);
    $content = $response->getBody()->getContents();
    $crawler = new Crawler($content);
    $links = $crawler->filter('ul#pagemenutabs li a')->evaluate('substring-after(@href, "")');
    $result = [];
    foreach($links as $link) {
        $response = $client->get($site . $link);
        $content = $response->getBody()->getContents();
        $crawler = new Crawler($content);
        $name = $crawler->filter('ul#pagemenutabs li.active a')->text();
        $crawler = $crawler->filter('table.table-heatmap')->filterXPath('//tr[contains(@class, "datatable-row")]');
        $data = [];
        foreach ($crawler as $domElement) {
            $string = trim($domElement->childNodes->item(1)->textContent);
            $date = $domElement->childNodes->item(7)->textContent;
            $date = date('Y-m-d', strtotime($date));
            $country = [
                'count_last' => $domElement->childNodes->item(3)->textContent,
                'count_before' => $domElement->childNodes->item(5)->textContent,
                'date' => $date,
            ];
            $data[$string] = $country;
        }
        $result[$name] = $data;
    }
    return response()->json($result, 200);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
