"""Write the three B-roll cutaway scenes (spine, wanita, peranakan) from one template."""
import sys

CUTS = [
    # id, image, label, accent, duration, card height, object-position, glow (x%, y%) or None
    ("cut-spine", "assets/img/spine.jpg", "SAKIT BELAKANG", "#FF5A5F", 1.6, 640, "50% 50%", (36, 72)),
    ("cut-wanita", "assets/img/wanita.jpg", "ANGKAT BERAT", "#E2B84B", 1.15, 760, "50% 30%", None),
    ("cut-peranakan", "assets/img/peranakan.jpg", "PERANAKAN JATUH", "#FF5A5F", 1.6, 640, "62% 50%", None),
]

TPL = """<template>
  <style>
    #root {{ position: absolute; inset: 0; pointer-events: none; }}
    #{p}-bg {{ position: absolute; inset: 0;
      background: radial-gradient(120% 80% at 50% 40%, #16503C 0%, #0B2A20 55%, #05140F 100%); }}
    #{p}-card {{ position: absolute; left: 80px; top: {top}px; width: 920px; height: {h}px;
      border-radius: 36px; overflow: hidden; border: 3px solid rgba(226,184,75,.85);
      box-shadow: 0 40px 90px rgba(0,0,0,.55), 0 0 0 10px rgba(226,184,75,.08); }}
    #{p}-img {{ position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: {pos}; }}
    #{p}-glow {{ position: absolute; left: {gx}%; top: {gy}%; width: 340px; height: 340px; margin: -170px 0 0 -170px;
      border-radius: 50%; background: radial-gradient(circle, rgba(255,60,60,.75) 0%, rgba(255,60,60,0) 70%);
      mix-blend-mode: screen; display: {gd}; }}
    #{p}-chip {{ position: absolute; left: 0; right: 0; top: {chip}px; text-align: center; }}
    #{p}-chip span {{ display: inline-block; padding: 14px 34px; border-radius: 999px; background: {accent};
      color: #0B1C15; font-family: "Montserrat", sans-serif; font-weight: 900; font-size: 40px; letter-spacing: 4px; }}
  </style>
  <div id="root" data-composition-id="{p}" data-width="1080" data-height="1920">
    <div id="{p}-bg" class="clip" data-start="0" data-duration="{d}" data-track-index="0"></div>
    <div id="{p}-card" class="clip" data-start="0" data-duration="{d}" data-track-index="1">
      <img id="{p}-img" src="{img}" alt="" />
      <div id="{p}-glow"></div>
    </div>
    <div id="{p}-chip" class="clip" data-start="0" data-duration="{d}" data-track-index="2"><span id="{p}-label">{label}</span></div>
  </div>
  <script>
    const tl = gsap.timeline({{ paused: true }});
    // whip in from the right, land blurred, sharpen
    tl.fromTo("#{p}-bg", {{ opacity: 0 }}, {{ opacity: 1, duration: 0.14, ease: "power2.out" }}, 0);
    tl.fromTo("#{p}-card", {{ x: 420, rotation: 4, filter: "blur(14px)", opacity: 0 }},
      {{ x: 0, rotation: 0, filter: "blur(0px)", opacity: 1, duration: 0.36, ease: "expo.out" }}, 0);
    tl.fromTo("#{p}-img", {{ scale: 1.18 }}, {{ scale: 1.04, duration: {d}, ease: "power1.out" }}, 0);
    tl.fromTo("#{p}-glow", {{ opacity: 0, scale: 0.6 }}, {{ opacity: 1, scale: 1, duration: 0.4, ease: "power2.out" }}, 0.3);
    tl.to("#{p}-glow", {{ opacity: 0.55, scale: 1.15, duration: 0.35, ease: "power2.inOut" }}, 0.75);
    tl.to("#{p}-glow", {{ opacity: 1, scale: 1, duration: 0.35, ease: "power2.inOut" }}, 1.1);
    tl.fromTo("#{p}-label", {{ opacity: 0, y: 24, scale: 0.9 }}, {{ opacity: 1, y: 0, scale: 1, duration: 0.3, ease: "back.out(1.6)" }}, 0.18);
    // exit: whip out to the left
    tl.to("#{p}-card", {{ x: -380, filter: "blur(12px)", opacity: 0, duration: 0.18, ease: "power3.in" }}, {xo});
    tl.to("#{p}-label", {{ opacity: 0, duration: 0.14, ease: "power2.in" }}, {xo});
    tl.to("#{p}-bg", {{ opacity: 0, duration: 0.16, ease: "power2.in" }}, {xb});
    window.__timelines["{p}"] = tl;
  </script>
</template>
"""

for p, img, label, accent, d, h, pos, glow in CUTS:
    top = 230 if h > 700 else 300
    gx, gy = glow if glow else (50, 50)
    out = TPL.format(p=p, img=img, label=label, accent=accent, d=d, h=h, pos=pos, top=top,
                     chip=top + h + 40, gx=gx, gy=gy, gd="block" if glow else "none",
                     xo=round(d - 0.2, 2), xb=round(d - 0.17, 2))
    open(f"{sys.argv[1]}/{p}.html", "w").write(out)
    print("wrote", p)
