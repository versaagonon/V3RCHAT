from PIL import Image

img = Image.open("animated.png")

print("Format:", img.format)
print("Frames:", getattr(img, "n_frames", 1))
print("Animated:", getattr(img, "is_animated", False))