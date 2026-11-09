<script>
    $(function () {
        function digitos(v) { return (v || '').replace(/\D/g, ''); }
        function mascCpf(v) {
            v = digitos(v).slice(0, 11);
            return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        function mascTel(v) {
            v = digitos(v).slice(0, 11);
            return v.length > 10 ? v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3') : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, '');
        }
        function mascCep(v) { return digitos(v).slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'); }

        $('#cpf').on('input', function () { this.value = mascCpf(this.value); }).trigger('input');
        $('.tel').on('input', function () { this.value = mascTel(this.value); }).trigger('input');
        $('#cep').on('input', function () { this.value = mascCep(this.value); }).trigger('input');

        $('#sem_cpf').on('change', function () { $('#cpf').prop('disabled', this.checked).val(this.checked ? '' : $('#cpf').val()); });
        $('#sem_rg').on('change', function () { $('#rg').prop('disabled', this.checked).val(this.checked ? '' : $('#rg').val()); });

        $('#cep').on('blur', function () {
            var cep = digitos(this.value);
            if (cep.length !== 8) { return; }
            $.getJSON('https://viacep.com.br/ws/' + cep + '/json/').done(function (d) {
                if (d.erro) { return; }
                if (!$('#estado').val()) { $('#estado').val(d.uf); }
                if (!$('#cidade').val()) { $('#cidade').val(d.localidade); }
                if (!$('#bairro').val()) { $('#bairro').val(d.bairro); }
                if (!$('#endereco').val()) { $('#endereco').val(d.logradouro); }
            });
        });
    });
</script>
