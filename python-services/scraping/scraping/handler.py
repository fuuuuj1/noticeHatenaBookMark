import json
import traceback
import requests
from bs4 import BeautifulSoup
from extractcontent3 import ExtractContent

def lambda_handler(event, context):
    # HTTPリクエストボディからURLリストを取得
    urls = json.loads(event['body'])['urls']

    results = []
    for url in urls:
        try:
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
        except Exception as e:
            print('Error_at: {}'.format(url))
            print('Error: {}'.format(e))
            print('Traceback: {}'.format(traceback.format_exc()))

            # エラーが発生したサイトの情報も返す形式に変更
            results.append({
                'url': url,
                'error': str(e)
            })

    # 結果をJSON形式で返す
    return {
        'statusCode': 200,
        'headers': {
            'Content-Type': 'application/json'
        },
        'body': json.dumps(results)
    }
