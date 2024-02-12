import json
import requests
from bs4 import BeautifulSoup
from extractcontent3 import ExtractContent

def lambda_handler(event, context):
    # HTTPリクエストボディからURLリストを取得
    urls = json.loads(event['body'])['urls']

    results = []
    for url in urls:
        # ウェブページからHTMLを取得
        res = requests.get(url)
        html = res.text

        # BeautifulSoupでタイトルを取得
        soup = BeautifulSoup(html, "html.parser")
        title = soup.find("title").text if soup.find("title") else ""

        # extractcontent3で本文を取得
        extractor = ExtractContent()
        extractor.analyse(html)
        text, _ = extractor.as_text()

        results.append({
            'url': url,
            'title': title,
            'content': text
        })

    # 結果をJSON形式で返す
    return {
        'statusCode': 200,
        'headers': {
            'Content-Type': 'application/json'
        },
        'body': json.dumps(results)
    }
