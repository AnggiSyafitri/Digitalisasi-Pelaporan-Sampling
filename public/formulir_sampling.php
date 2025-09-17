<?php
session_start();
// Cek sesi dan peran PPC
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pengambilan Contoh Sampling</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background-color: #f8f9fa; 
            color: #333;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .header a {
            color: #ffc107;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .container { 
            max-width: 900px; 
            margin: 2rem auto; 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }
        h3 { 
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-top: 0;
        }
        .form-section { 
            margin-bottom: 2rem; 
            border-bottom: 1px solid #dee2e6; 
            padding-bottom: 1.5rem; 
        }
        .form-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600; 
            color: #555;
        }
        input[type="text"], input[type="date"], textarea, select { 
            width: 100%; 
            padding: 0.75rem; 
            margin-bottom: 1rem; 
            border-radius: 6px; 
            border: 1px solid #ced4da; 
            box-sizing: border-box; 
            transition: border-color 0.2s, box-shadow 0.2s;
            font-size: 1rem;
        }
        input[type="text"]:focus, input[type="date"]:focus, textarea:focus, select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            outline: none;
        }
        .btn { 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            color: white; 
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-primary { background-color: #28a745; }
        .btn-primary:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .contoh-item { 
            border: 1px solid #007bff; 
            padding: 1.5rem; 
            margin-top: 1.5rem; 
            border-radius: 8px; 
            background-color: #f8f9fa;
            position: relative;
        }
        .contoh-item h4 {
            margin-top: 0;
            color: #343a40;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .parameter-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 0.75rem;
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .parameter-grid label { 
            font-weight: normal; 
            display: flex;
            align-items: center;
            background: #fff;
            padding: 0.5rem;
            border-radius: 4px;
        }
        .parameter-grid input[type="checkbox"] {
            margin-right: 0.5rem;
            width: auto;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            margin-top: 1.5rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header class="header">
    <h2>Formulir Pengambilan Contoh</h2>
    <span>Login sebagai: <b><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></b> | <a href="logout.php">Logout</a></span>
</header>

<div class="container">
    <form action="simpan_sampling.php" method="post" enctype="multipart/form-data">

        <div class="form-section">
            <h3>Informasi Umum</h3>
            <label for="perusahaan">Nama Perusahaan:</label>
            <input type="text" id="perusahaan" name="perusahaan" required>

            <label for="alamat">Alamat:</label>
            <textarea id="alamat" name="alamat" rows="3" required></textarea>

            <label for="tanggal">Tanggal Sampling:</label>
            <input type="date" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-section">
            <h3>Data Contoh Uji</h3>
            <p>Tambahkan satu atau lebih contoh uji yang diambil selama kegiatan sampling.</p>
            <button type="button" class="btn btn-secondary" onclick="tambahContoh()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Tambah Contoh Uji
            </button>
            <div id="contohContainer"></div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                </svg>
                Simpan & Ajukan Laporan
            </button>
        </div>
    </form>
    <a href="dashboard.php" class="back-link">⬅ Kembali ke Dashboard</a>
</div>

<script>
    const dataSampling = {
        "Air Limbah": {
            jenisContoh: ["Air Limbah Industri", "Air Limbah Domestik"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "COD sebagai O2", "Minyak dan Lemak", "Sulfat", "Cadmium (Cd)total", "Nikel (Ni) total", "Chrom (Cr) total", "Seng (Zn) total", "Mangan (Mn) terlarut", "Tembaga (Cu) total", "Timbal (Pb) total", "Besi (Fe) Terlarut", "Barium (Ba) total", "Cobalt (Co) total", "Padatan Terlarut Total (TDS Meter)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Krom Hexavalen (Cr-VI)", "Nitrit ( NO2 )", "Total Coliform"],
            prosedur: ["SNI 8990 : 2021 (Metode Pangambilan Contoh Air Limbah)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Permukaan": {
            jenisContoh: ["Air Sungai", "Air Danau", "Air Waduk", "Air Kolam", "Air Parit", "Air Irigasi"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Total Coliform", "Fecal Coliform"],
            prosedur: ["SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Tanah": {
            jenisContoh: ["Air Sumur Bor", "Air Sumur", "Sumur Artesis", "Sumber Mata Air"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Aluminium (Al) terlarut", "Kalium (K) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Escherichia coli", "Total Coliform"],
            prosedur: ["SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Udara": {
            jenisContoh: ["Udara Emisi dari sumber bergerak", "Udara Emisi dari sumber tidak bergerak", "Udara Ambien"],
            parameter: {
                "Udara Emisi dari sumber bergerak": ["Opasitas"],
                "Udara Ambien": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Carbon monoksida (CO)", "TSP", "Timbal (Pb)", "Oksidan (O3)", "Amoniak (NH3)", "Hidrogen sulfida (H2S)"],
                "Udara Emisi dari sumber tidak bergerak": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Karbon monoksida (CO)", "Nitrogen Oksida (NOx)", "Oksigen (O2)", "Karbon dioksida (CO2)", "Opasitas", "Kecepatan Linier/Laju alir (velocity)", "Berat Molekul kering", "Kadar Uap Air", "Partikulat"]
            },
            prosedur: {
                "Udara Emisi dari sumber bergerak": ["M-LP-714-SMO (Smoke Meter Opacity) (Pengambilan contoh uji udara sumber emisi bergerak)"],
                "Udara Ambien": ["SNI 19-7119.6-2005 (Metode Pengambilan Contoh Udara Ambien)", "SNI 19-7119.9-2005 (Metode Pengambilan Contoh Udara Roadside)"],
                "Udara Emisi dari sumber tidak bergerak": ["SNI 7117.13-2009 (Pengambilan contoh uji udara emisi tidak bergerak)"]
            }
        },
        "Tingkat Kebisingan": {
            jenisContoh: null,
            parameter: ["Kebisingan"],
            prosedur: ["SNI 8427 : 2017 (Metode Pengambilan contoh uji kebisingan)"]
        },
        "Tingkat Getaran": {
            jenisContoh: null,
            parameter: ["Getaran"],
            prosedur: ["M-LP-711-GET (Vibration Meter) (Metode Pengambilan contoh uji Getaran)"]
        }
    };

    let contohCounter = 0;

    function tambahContoh() {
        const container = document.getElementById("contohContainer");
        const div = document.createElement("div");
        div.className = "contoh-item";
        div.id = `contoh_item_${contohCounter}`;
        const currentCounter = contohCounter;

        const namaContohOptions = Object.keys(dataSampling).map(key => `<option value="${key}">${key}</option>`).join('');

        div.innerHTML = `
            <h4>
                <span>Contoh Uji #${currentCounter + 1}</span>
                <button type="button" class="btn btn-danger" onclick="hapusContoh(${currentCounter})">Hapus</button>
            </h4>
            <input type="hidden" name="contoh[${currentCounter}][tipe_laporan]" id="tipe_laporan_${currentCounter}">

            <label for="nama_contoh_${currentCounter}">1. Nama Contoh (Bahan):</label>
            <select name="contoh[${currentCounter}][nama_contoh]" id="nama_contoh_${currentCounter}" onchange="updateDynamicFields(${currentCounter})" required>
                <option value="">-- Pilih Bahan --</option>
                ${namaContohOptions}
            </select>

            <div id="jenis_contoh_wrapper_${currentCounter}" style="display:none;">
                <label for="jenis_contoh_${currentCounter}">2. Jenis Contoh (Produk Uji):</label>
                <select name="contoh[${currentCounter}][jenis_contoh]" id="jenis_contoh_${currentCounter}" onchange="updateParameters(${currentCounter})">
                    <option value="">-- Pilih Jenis Contoh --</option>
                </select>
            </div>

            <label>3. Parameter Uji:</label>
            <div class="parameter-grid" id="parameter_container_${currentCounter}">
                <p>Pilih Nama dan Jenis Contoh terlebih dahulu.</p>
            </div>

            <label for="prosedur_${currentCounter}">4. Prosedur Pengambilan Contoh:</label>
            <select name="contoh[${currentCounter}][prosedur]" id="prosedur_${currentCounter}" required>
                <option value="">-- Pilih Prosedur --</option>
            </select>
            
            <label for="baku_mutu_${currentCounter}">5. Baku Mutu:</label>
            <select name="contoh[${currentCounter}][baku_mutu]" id="baku_mutu_${currentCounter}">
                <option value="">-- Pilih Baku Mutu --</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran I">PP RI No. 22 Tahun 2021, Lampiran I</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran III">PP RI No. 22 Tahun 2021, Lampiran III</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran IV">PP RI No. 22 Tahun 2021, Lampiran IV</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran VII">PP RI No. 22 Tahun 2021, Lampiran VII</option>
                <option value="PERMENLH No. 05 Tahun 2014 Lampiran III">PERMENLH No. 05 Tahun 2014 Lampiran III</option>
                <option value="PERMEN LHK No. 14 Tahun 2020">Permen LHK No. 14 Tahun 2020</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996</option>                
                <option value="Lainnya">Lainnya...</option>
            </select>
            <input type="text" name="contoh[${currentCounter}][baku_mutu_lainnya]" id="baku_mutu_lainnya_${currentCounter}" style="display:none; margin-top: 10px;" placeholder="Masukkan baku mutu lainnya">

            <label>Etiket / Merek:</label>
            <input type="text" name="contoh[${currentCounter}][merek]">
            
            <label>Kode:</label>
            <input type="text" name="contoh[${currentCounter}][kode]">
            
            <label>Catatan Contoh:</label>
            <textarea name="contoh[${currentCounter}][catatan]" rows="2"></textarea>
        `;
        container.appendChild(div);
        
        document.getElementById(`baku_mutu_${currentCounter}`).addEventListener('change', function() {
            const lainnyaInput = document.getElementById(`baku_mutu_lainnya_${this.id.split('_')[2]}`);
            lainnyaInput.style.display = (this.value === 'Lainnya') ? 'block' : 'none';
            if (this.value !== 'Lainnya') lainnyaInput.value = '';
        });

        contohCounter++;
    }
    
    function hapusContoh(id) {
        const item = document.getElementById(`contoh_item_${id}`);
        if (item) {
            item.remove();
        }
    }

    function updateDynamicFields(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];

        const jenisContohWrapper = document.getElementById(`jenis_contoh_wrapper_${id}`);
        const jenisContohSelect = document.getElementById(`jenis_contoh_${id}`);
        const tipeLaporanInput = document.getElementById(`tipe_laporan_${id}`);

        tipeLaporanInput.value = (selectedNamaContoh.includes("Udara") || selectedNamaContoh.includes("Kebisingan") || selectedNamaContoh.includes("Getaran")) ? 'udara' : 'air';

        if (data && data.jenisContoh) {
            jenisContohWrapper.style.display = 'block';
            jenisContohSelect.innerHTML = '<option value="">-- Pilih Jenis --</option>' + data.jenisContoh.map(jc => `<option value="${jc}">${jc}</option>`).join('');
        } else {
            jenisContohWrapper.style.display = 'none';
            jenisContohSelect.innerHTML = '';
        }

        updateParameters(id);
        updateProsedur(id);
    }

    function updateParameters(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const parameterContainer = document.getElementById(`parameter_container_${id}`);

        let parameters = [];
        if (data) {
            if (Array.isArray(data.parameter)) {
                parameters = data.parameter;
            } else if (typeof data.parameter === 'object' && selectedJenisContoh) {
                parameters = data.parameter[selectedJenisContoh] || [];
            }
        }
        
        if (parameters.length > 0) {
            parameterContainer.innerHTML = parameters.map(p => 
                `<label><input type="checkbox" name="contoh[${id}][parameter][]" value="${p}"> ${p}</label>`
            ).join('');
        } else {
            parameterContainer.innerHTML = '<p>Pilih Nama dan Jenis Contoh terlebih dahulu.</p>';
        }
    }
    
    function updateProsedur(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const prosedurSelect = document.getElementById(`prosedur_${id}`);

        let prosedur = [];
        if (data) {
            if (Array.isArray(data.prosedur)) {
                prosedur = data.prosedur;
            } else if (typeof data.prosedur === 'object' && selectedJenisContoh) {
                prosedur = data.prosedur[selectedJenisContoh] || [];
            }
        }

        if(prosedur.length > 0) {
            prosedurSelect.innerHTML = '<option value="">-- Pilih Prosedur --</option>' + prosedur.map(p => `<option value="${p}">${p}</option>`).join('');
        } else {
            prosedurSelect.innerHTML = '<option value="">-- Pilihan tidak tersedia --</option>';
        }
    }
</script>


</body>
</html>