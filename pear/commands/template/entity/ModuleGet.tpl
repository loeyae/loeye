      - name: \loeye\plugin\JWTPlugin
        format: json
        out: login_info
      - name: \loeye\plugin\CheckRequestMethodPlugin
        allowed: GET
      - name: \loeye\plugin\ValidatePlugin
        entity: <{$entityFullName}>
        type: 101
        groups: query
      - name: \loeye\plugin\OutputPlugin
        if: __lyHasError[ValidatePlugin_validate_error]
        validate_error: ValidatePlugin_validate_error
      - name: <{$pluginFullName}>
        inputs:
            <{$pluginName}>_input: $_PATH[id]
      - name: \loeye\plugin\OutputPlugin
        output_data: <{$pluginName}>_output
        error: <{$pluginName}>_errors