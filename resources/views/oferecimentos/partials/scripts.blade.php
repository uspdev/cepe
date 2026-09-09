@section('javascripts_bottom')
    @parent
    <script>
        $(function () {
            function toggleFormasPagamento() {
                var gratuito = $('#gratuito_ou_sem_pagamento').is(':checked');
                $('#pagamento_pix, #pagamento_boleto').prop('disabled', gratuito);
                if (gratuito) {
                    $('#pagamento_pix, #pagamento_boleto').prop('checked', false);
                }
            }
            $('#gratuito_ou_sem_pagamento').on('change', toggleFormasPagamento);
            toggleFormasPagamento();
        });
    </script>
@endsection
