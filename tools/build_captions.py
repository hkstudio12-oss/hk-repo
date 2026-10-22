"""Build compositions/captions.html from the whisper word timings.

Kedah-dialect words are corrected by position (never by text match).
Usage: python3 -I tools/build_captions.py _work/transcript2.json almukmin-promo/compositions/captions.html
"""
import html
import json
import sys

FIX = {2: "penyapu", 7: "hangpa", 17: "hangpa", 19: "dok", 20: "biaq.", 21: "Pi", 24: "pulak,",
       28: "tercabut!", 29: "dok", 30: "biaq", 31: "biaq,", 33: "hangpa", 34: "pulak",
       36: "Hangpa", 38: "la", 47: "pakej,", 67: "hangpa", 72: "naikkan", 80: "hangpa", 82: "hangpa",
       13: "taktau", 14: None, 16: "habaq,", 45: "macam-", 46: "macam", 32: "peranakan"}
GROUPS = [[7, 8, 9], [10, 11, 12], [13, 14, 15, 16], [17, 18, 19, 20], [21, 22, 23, 24],
          [25, 26, 27, 28], [29, 30, 31, 32], [33, 34, 35], [36, 37, 38, 39],
          [43, 44, 45, 46, 47], [48, 49, 50, 51], [52, 53], [54, 55, 56, 57],
          [58, 59, 60, 61, 62], [63, 64, 65, 66], [67, 68, 69, 70], [71, 72, 73, 74],
          [75, 76, 77, 78], [79, 80, 81], [82, 83, 84], [85, 86, 87, 88, 89]]
GOLD = {11, 23, 32, 40, 41, 42, 49, 50, 52, 54, 55, 61, 66, 77, 84, 87}
RED = {10, 28, 35, 69}
END = 29.86  # footage is 29.906 s; clamp captions inside it

words = [w for s in json.load(open(sys.argv[1])) for w in s["words"]]
groups_html, js = [], []
starts = [max(0.0, round(words[g[0]]["s"] - 0.06, 2)) for g in GROUPS]
for gi, g in enumerate(GROUPS):
    start = starts[gi]
    natural = words[g[-1]]["e"] + 0.3
    end = min(natural, starts[gi + 1]) if gi + 1 < len(GROUPS) else END
    end = round(min(end, END), 2)
    spans = []
    for i in g:
        text = FIX.get(i, words[i]["w"].strip().lstrip("-"))
        if text is None:
            continue
        if i == 45:
            text = "macam-"
        cls = "w gold" if i in GOLD else "w red" if i in RED else "w"
        spans.append(f'<span id="cw{i}" class="{cls}">{html.escape(text)}</span>')
        if i not in GOLD and i not in RED:
            js.append(f'  tl.fromTo("#cw{i}", {{ color: "#ffffff" }}, {{ color: "#F3D27A", duration: 0.08 }}, {words[i]["s"]});')
        else:
            js.append(f'  tl.fromTo("#cw{i}", {{ scale: 1 }}, {{ scale: 1.12, duration: 0.12, ease: "back.out(2)" }}, {words[i]["s"]});')
            js.append(f'  tl.to("#cw{i}", {{ scale: 1, duration: 0.25, ease: "power2.out" }}, {words[i]["s"] + 0.14:.2f});')
    gid = f"cg{gi}"
    groups_html.append(
        f'    <div id="{gid}" class="clip cap" data-start="{start}" data-duration="{round(end - start, 2)}" data-track-index="0">'
        f'<div class="cap-inner"><span class="cap-box">{"".join(spans)}</span></div></div>')
    js.append(f'  tl.fromTo("#{gid} .cap-inner", {{ opacity: 0, y: 14, filter: "blur(6px)" }}, {{ opacity: 1, y: 0, filter: "blur(0px)", duration: 0.16, ease: "power3.out" }}, {start});')
    js.append(f'  tl.to("#{gid} .cap-inner", {{ opacity: 0, duration: 0.1, ease: "power2.in" }}, {round(end - 0.1, 2)});')
    js.append(f'  tl.set("#{gid} .cap-inner", {{ opacity: 0, visibility: "hidden" }}, {end});')

out = f"""<template>
  <style>
    #root {{ position: absolute; inset: 0; pointer-events: none; }}
    .cap {{ position: absolute; left: 0; right: 0; top: 1150px; height: 170px; overflow: visible; }}
    .cap-inner {{
      width: 100%; padding: 0 90px; text-align: center;
      font-family: "Montserrat", sans-serif; font-weight: 800; font-size: 68px; line-height: 1.12;
      color: #fff; letter-spacing: -0.5px;
      text-shadow: 0 4px 18px rgba(0,0,0,.65), 0 2px 4px rgba(0,0,0,.8);
    }}
    .w {{ display: inline-block; margin: 0 0.11em; transform-origin: 50% 70%; }}
    .cap-box {{ display: inline; padding: 6px 14px; border-radius: 18px; background: rgba(6,20,14,.34);
      -webkit-box-decoration-break: clone; box-decoration-break: clone; line-height: 1.32; }}
    .w.gold {{ color: #F3D27A; }}
    .w.red {{ color: #FF5A5F; }}
  </style>
  <div id="root" data-composition-id="captions" data-width="1080" data-height="1920">
{chr(10).join(groups_html)}
  </div>
  <script>
  const tl = gsap.timeline({{ paused: true }});
{chr(10).join(js)}
  window.__timelines["captions"] = tl;
  </script>
</template>
"""
open(sys.argv[2], "w").write(out)
print("groups", len(GROUPS))
