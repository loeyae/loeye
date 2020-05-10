      - name: \loeye\plugin\JWTPlugin
        format: json
        out: login_info
      - name: \loeye\plugin\CheckRequestMethodPlugin
        allowed: POST
      - name: \loeye\plugin\BuildQueryPlugin
        type: 100
        prefix: <{$pluginName}>
        validate: <{$entityFullName}>
        criteria: 1
      - name: \loeye\plugin\OutputPlugin
        if: __lyHasError[BuildQueryPlugin_errors]
        validate_error: BuildQueryPlugin_errors
      - name: <{$pluginFullName}>
      - name: \loeye\plugin\OutputPlugin
        format: json
        output_data: <{$pluginName}>_output
        error: <{$pluginName}>_errors