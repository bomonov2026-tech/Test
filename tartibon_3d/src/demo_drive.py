# Demo: TartibOn krossoveri ko'cha bo'ylab kelib, piyodalar o'tish joyi oldida to'xtaydi.
# Ishga tushirish: python3 demo_drive.py <out_dir> [birinchi_kadr oxirgi_kadr]
import math
import os
import sys

import bpy
from mathutils import Vector

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import car  # noqa: E402

out = os.path.abspath(sys.argv[1])
FPS, N = 24, 192
X0, X1, STOP_F = -14.0, 4.3, 150   # boshlang'ich/to'xtash nuqtasi, to'xtash kadri
LANE_Y = -1.75

bpy.ops.wm.read_factory_settings(use_empty=True)
sc = bpy.context.scene
root, wheels, mats = car.build_car(os.path.join(out, "livery.png"))
root.location = (X0, LANE_Y, 0)

M = car.material
asphalt = M("Asphalt", (0.035, 0.036, 0.04), rough=0.85)
paint = M("Road_paint", (0.8, 0.8, 0.8), rough=0.6)
curb = M("Curb", (0.45, 0.45, 0.45), rough=0.8)
walk = M("Sidewalk", (0.32, 0.31, 0.3), rough=0.9)
grass = M("Grass", (0.06, 0.16, 0.04), rough=0.9)
wall = M("Building", (0.55, 0.5, 0.42), rough=0.8)
leaf = M("Leaves", (0.05, 0.2, 0.05), rough=0.8)
trunk = M("Trunk", (0.1, 0.06, 0.03), rough=0.9)

B = lambda n, loc, size, m, rot=(0, 0, 0): car.box(n, loc, size, m, None, rot=rot)
B("Ground", (0, 0, -0.06), (300, 120, 0.1), grass)
B("Road", (0, 0, -0.005), (300, 7.0, 0.01), asphalt)
for s in (1, -1):
    B(f"Curb_{s}", (0, s * 3.6, 0.07), (300, 0.2, 0.15), curb)
    B(f"Walk_{s}", (0, s * 5.2, 0.06), (300, 3.0, 0.12), walk)
    for i in range(-6, 7):
        x = i * 14 + (5 if s > 0 else 0)
        car.cylinder(f"Trunk_{s}_{i}", (x, s * 6.0, 1.2), 0.12, 2.4, trunk, None, axis="Z", segs=12)
        bpy.ops.mesh.primitive_ico_sphere_add(subdivisions=3, radius=1.6, location=(x, s * 6.0, 3.2))
        bpy.context.object.data.materials.append(leaf)
    for i in range(-5, 6):
        h = 8 + 5 * ((i * 7919 + (s > 0) * 13) % 5)
        B(f"Bld_{s}_{i}", (i * 22, s * 16, h / 2), (16, 10, h), wall)
for i in range(-20, 21):
    if not 6 < i * 9 < 14:
        B(f"Dash_{i}", (i * 9, 0, 0.002), (3.0, 0.15, 0.006), paint)
for k in range(7):  # zebra: yo'l o'qiga parallel oq polosalar
    B(f"Zebra_{k}", (10.0, -3.0 + k * 1.0, 0.002), (4.0, 0.5, 0.006), paint)

w = bpy.data.worlds.new("World")
sc.world = w
w.use_nodes = True
w.node_tree.nodes["Background"].inputs[0].default_value = (0.55, 0.7, 0.95, 1)
w.node_tree.nodes["Background"].inputs[1].default_value = 1.0
bpy.ops.object.light_add(type="SUN", rotation=(math.radians(40), math.radians(15), math.radians(120)))
bpy.context.object.data.energy = 4.0
bpy.context.object.data.angle = math.radians(2)

# Harakat: tekis yaqinlashib, silliq sekinlashib to'xtash (har kadrga kalit)
tail = mats.tail.node_tree.nodes["Principled BSDF"].inputs["Emission Strength"]
dist_total = X1 - X0
prev_x = X0
for f in range(1, N + 1):
    t = min((f - 1) / (STOP_F - 1), 1.0)
    # 0..0.55 o'zgarmas tezlik, keyin kvadratik sekinlashish
    k = 0.55
    v0 = 1.0 / (k + (1 - k) / 2)
    s = v0 * t if t <= k else v0 * k + v0 * (t - k) - v0 * (t - k) ** 2 / (2 * (1 - k))
    x = X0 + dist_total * min(s, 1.0)
    root.location.x = x
    root.keyframe_insert("location", index=0, frame=f)
    for nm, (steer, spin) in wheels.items():
        spin.rotation_euler.y += (x - prev_x) / car.WHEEL_R
        spin.keyframe_insert("rotation_euler", index=1, frame=f)
    braking = k < t < 1.0 or f > STOP_F
    tail.default_value = 8.0 if braking else 2.0
    tail.keyframe_insert("default_value", frame=f)
    prev_x = x

for o in list(bpy.data.objects):
    ad = o.animation_data
    if ad and ad.action:
        for layer in getattr(ad.action, "layers", []):
            for strip in layer.strips:
                for cb in strip.channelbags:
                    for fc in cb.fcurves:
                        for kp in fc.keyframe_points:
                            kp.interpolation = "LINEAR"

# Kamera: piyodalar o'tish joyi yonidan mashinani kuzatadi
target = bpy.data.objects.new("CamTarget", None)
car.link(target, root)
target.location = (0.3, 0, 0.8)
cam = bpy.data.objects.new("Cam", bpy.data.cameras.new("Cam"))
sc.collection.objects.link(cam)
sc.camera = cam
cam.data.lens = 35
cam.location = (13.5, -7.5, 1.7)
cam.keyframe_insert("location", frame=1)
cam.location = (12.0, -6.0, 1.5)
cam.keyframe_insert("location", frame=N)
tc = cam.constraints.new("TRACK_TO")
tc.target, tc.track_axis, tc.up_axis = target, "TRACK_NEGATIVE_Z", "UP_Y"

sc.render.engine = "CYCLES"
sc.cycles.device = "CPU"
sc.cycles.samples = int(os.environ.get("SAMPLES", 16))
sc.cycles.use_denoising = True
sc.render.resolution_x, sc.render.resolution_y = 1280, 720
sc.render.fps = FPS
sc.view_settings.look = "AgX - Medium High Contrast"
sc.frame_start = int(sys.argv[2]) if len(sys.argv) > 2 else 1
sc.frame_end = int(sys.argv[3]) if len(sys.argv) > 3 else N
os.makedirs(os.path.join(out, "demo_frames"), exist_ok=True)
sc.render.filepath = os.path.join(out, "demo_frames", "f_")
bpy.ops.wm.save_as_mainfile(filepath=os.path.join(out, "demo_drive.blend"))
bpy.ops.render.render(animation=True)
