      - name: \loeye\plugin\JWTPlugin
        format: json
        out: login_info
      - name: \loeye\plugin\CheckRequestMethodPlugin
        allowed: POST
      - name: \loeye\plugin\ValidatePlugin
        entity: <{$entityFullName}>
        type: 100
        groups: create
      - name: \loeye\plugin\OutputPlugin
        if: __lyHasError[ValidatePlugin_validate_error]
        validate_error: ValidatePlugin_validate_error
      - name: <{$pluginFullName}>
        inputs:
          MapInsertPlugin_input: $_CONTEXT[ValidatePlugin_filter_data]
      - name: \loeye\plugin\OutputPlugin
        output_data: <{$pluginName}>_output
        error: <{$pluginName}>_errors