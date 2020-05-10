      - name: \loeye\plugin\JWTPlugin
        format: json
        inputs:
          encrypt_data:         #此字段为需要加密到token中的数据，应通过其他方式获取，且不要包含密码字段
            id: 1
            name: default
      - name: \loeye\plugin\OutputPlugin
        output_data: jwt