@extends('laporan.pdf.layout')

@section('judul', 'Saldo Cuti per Karyawan')

@section('keterangan-filter')
    {{ $keteranganFilter }}
@endsection

@section('isi')
<table class="data">
    <thead><tr><th>NIP</th><th>Nama</th><th>Unit</th><th>Jatah</th><th>Terpakai</th><th>Sisa</th></tr></thead>
    <tbody>
        @foreach ($saldo as $r)
            <tr>
                <td>{{ $r['nip'] }}</td>
                <td>{{ $r['nama'] }}</td>
                <td>{{ $r['unit'] }}</td>
                <td>{{ $r['jatah'] }}</td>
                <td>{{ $r['terpakai'] }}</td>
                <td>{{ $r['sisa'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="meta" style="margin-top:8px">Total: {{ $saldo->count() }} karyawan (eligible)</div>
@endsection
