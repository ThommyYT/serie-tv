import cloudscraper

scraper = cloudscraper.create_scraper()
proxies = {
    'http': 'socks5h://127.0.0.1:9050',
    'https': 'socks5h://127.0.0.1:9050'
}

# Ora la richiesta uscirà con l'IP di Tor, non quello di GitHub
response = scraper.get("https://uprot.net", proxies=proxies)
print(response.status_code)
