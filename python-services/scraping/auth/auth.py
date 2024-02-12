import os
import traceback

def handler(event, context):

    response = {
        "isAuthorized": False
    }

    # リクエストからヘッダーに含まれる認証情報を安全に取得
    token = event.get("headers", {}).get("authorization", None)

    # 環境変数から設定されたシークレットトークンを取得
    secret_token = os.environ.get("SECRET_TOKEN")

    try:
        if (token == secret_token):

            # リクエストを許可
            # isAuthorized = True としてリクエストを許可する
            # context には後続のlambdaに渡すための情報を設定できる
            response = {
                "isAuthorized": True
            }
            print('allowed')
            return response
        else:
            print('denied')
            return response
    except BaseException:
        # エラー内容をログに出力
        print('Error: {}'.format(BaseException))
        print('Traceback: {}'.format(traceback.format_exc()))
        return response
