class Bitrix24API
{
    private $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = rtrim($webhookUrl, '/') . '/';
    }

    /**
     * Вызов метода REST API Битрикс24.
     *
     * @param string $method
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function call(string $method, array $params = []): array
    {
        $url = $this->webhookUrl . $method;
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200 || $response === false) {
            throw new Exception("Ошибка вызова API метода $method. HTTP-код: $httpCode");
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
        }

        return $result;
    }
}
