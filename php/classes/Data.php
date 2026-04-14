<?php

namespace classes;

use \CurlHandle;

class Data
{
    private CurlHandle | null $ch = null; // Cambiato in null inizialmente
    private array $options;
    private string $html = "";

    /**
     * Class constructor.
     *
     * Initializes the cURL object and sets the default options
     * for the QueryPath object.
     */
    public function __construct()
    {
        $this->initCurl(); // Initializes the cURL object

        /**
         * Default options for the QueryPath object.
         *
         * @var array $options Options for the QueryPath object.
         */
        $this->options = [
            'convert_from_encoding' => 'UTF-8',
            'convert_to_encoding'   => 'UTF-8',
            'strip_low_ascii'       => false,
            'ignore_parser_warnings' => true
        ];
    }

    /**
     * Initializes the cURL object.
     *
     * This method sets the options for the cURL object.
     */
    private function initCurl()
    {
        /**
         * The cURL object to use.
         *
         * @var CurlHandle $ch
         */
        $this->ch = curl_init("http://flaresolverr:8191/v1");
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
    }


    /**
     * Saves only the options and the html string.
     *
     * This method is called automatically when the object is serialized.
     * It must return an array with the names of the properties to save.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['options', 'html']; // Salva solo le opzioni
    }

    /**
     * Riapre il cURL quando l'oggetto viene ripreso dalla sessione.
     *
     * Questo metodo viene chiamato automaticamente quando l'oggetto viene ripreso dalla sessione.
     * Riapre il cURL per essere pronto ad essere utilizzato.
     */
    public function __wakeup()
    {
        $this->initCurl(); // Reinizializza il cURL
    }

    /**
     * Returns the default options for the QueryPath object.
     *
     * @return array The default options for the QueryPath object.
     */
    public function getOptionsQP(): array
    {
        /**
         * The default options for the QueryPath object.
         *
         * @var array $options The default options for the QueryPath object.
         */
        return $this->options;
    }

    /**
     * Returns the cURL handle.
     *
     * This method returns the cURL handle that is used by the object to
     * make requests to the server. If the cURL handle is not set (i.e.
     * it is null), then it is initialized before being returned.
     *
     * @return CurlHandle|false The cURL handle or false if it could not be initialized.
     */
    public function getCH(): CurlHandle|false
    {
        // If the cURL handle is not set, then initialize it
        if (!$this->ch) {
            // Initialize the cURL handle
            $this->initCurl();
        }
        // Aggiungi queste opzioni per vedere errori dettagliati
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        // Return the cURL handle
        return $this->ch;
    }

    /**
     * Sets the HTML content of the object.
     *
     * This method sets the HTML content of the object. The HTML content is used
     * by the QueryPath object to parse the HTML and execute the CSS selectors.
     *
     * @param string $html The HTML content to set.
     */
    public function setHTML($html)
    {
        $this->html = $html;
    }

    /**
     * Returns the HTML content of the object.
     *
     * This method returns the HTML content of the object. The HTML content is
     * used by the QueryPath object to parse the HTML and execute the CSS
     * selectors.
     *
     * @return string The HTML content of the object.
     */
    public function getHTML()
    {
        return $this->html;
    }
}
