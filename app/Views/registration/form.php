<style>
body { background:#f3f7fb; font-family:"Noto Sans Bengali","Hind Siliguri",sans-serif; }
.simple-register { min-height:100vh; display:flex; align-items:center; padding:24px 12px; }
.simple-register .card { max-width:520px; margin:auto; border:0; border-radius:24px; box-shadow:0 18px 50px rgba(31,50,81,.12); }
.simple-register .card-body { padding:clamp(24px,6vw,48px); }
.simple-register .form-control { min-height:56px; border-radius:14px; font-size:17px; }
.simple-register .btn { min-height:58px; border-radius:14px; font-size:18px; font-weight:700; }
.trust-note { color:#667085; font-size:14px; }
</style>
<main class="simple-register">
  <div class="container"><div class="card"><div class="card-body">
    <div class="text-center mb-4">
      <h1 class="h3 fw-bold mb-2">১ মিনিটে শুরু করুন</h1>
      <p class="text-muted mb-0">স্কুলের কাজ সহজ করতে শুধু ৩টি তথ্য দিন</p>
    </div>
    <?= get_system_message(); ?>
    <?= form_open('registration', ['method' => 'post']) ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">মোবাইল নম্বর</label>
        <input type="tel" inputmode="numeric" autocomplete="tel" name="phone" value="<?= esc(old('phone')) ?>" class="form-control" placeholder="01XXXXXXXXX" pattern="(?:\+?88)?01[3-9][0-9]{8}" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">পাসওয়ার্ড</label>
        <input type="password" autocomplete="new-password" name="password" class="form-control" placeholder="কমপক্ষে ৬ অক্ষর" minlength="6" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">স্কুলের নাম</label>
        <input type="text" autocomplete="organization" name="school_name" value="<?= esc(old('school_name')) ?>" class="form-control" placeholder="আপনার স্কুলের নাম" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">অ্যাকাউন্ট তৈরি করে শুরু করুন</button>
    <?= form_close(); ?>
    <p class="trust-note text-center mt-3 mb-0">OTP, ইমেইল বা EIIN লাগবে না</p>
    <p class="text-center mt-4 mb-0">আগে থেকেই অ্যাকাউন্ট আছে? <a href="<?= site_url('login') ?>">লগইন করুন</a></p>
  </div></div></div>
</main>
