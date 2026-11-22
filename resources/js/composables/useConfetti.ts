type Flake = {
    x: number;
    y: number;
    velocityX: number;
    velocityY: number;
    rotation: number;
    spin: number;
    size: number;
    color: string;
    shape: 'strip' | 'dot';
};

const colors = ['#a03731', '#274c39', '#b0852f', '#e9d9b5', '#5f7f63'];

/**
 * Throws a short burst of paper confetti from a point on screen.
 * Does nothing when the visitor prefers reduced motion.
 */
export function useConfetti(): {
    burst: (origin?: { x: number; y: number }) => void;
} {
    function burst(origin?: { x: number; y: number }): void {
        if (
            typeof window === 'undefined' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        const ratio = window.devicePixelRatio || 1;
        canvas.width = window.innerWidth * ratio;
        canvas.height = window.innerHeight * ratio;
        canvas.setAttribute('aria-hidden', 'true');
        Object.assign(canvas.style, {
            position: 'fixed',
            inset: '0',
            width: '100%',
            height: '100%',
            pointerEvents: 'none',
            zIndex: '60',
        });
        document.body.appendChild(canvas);
        context.scale(ratio, ratio);

        const startX = origin?.x ?? window.innerWidth / 2;
        const startY = origin?.y ?? window.innerHeight / 2;
        const flakes: Flake[] = Array.from({ length: 140 }, () => {
            const angle = Math.random() * Math.PI * 2;
            const speed = 4 + Math.random() * 9;

            return {
                x: startX,
                y: startY,
                velocityX: Math.cos(angle) * speed,
                velocityY: Math.sin(angle) * speed - 6,
                rotation: Math.random() * Math.PI,
                spin: (Math.random() - 0.5) * 0.3,
                size: 5 + Math.random() * 6,
                color: colors[Math.floor(Math.random() * colors.length)],
                shape: Math.random() > 0.3 ? 'strip' : 'dot',
            };
        });
        const startedAt = performance.now();

        function frame(now: number): void {
            const elapsed = now - startedAt;
            context!.clearRect(0, 0, window.innerWidth, window.innerHeight);
            context!.globalAlpha = Math.max(0, 1 - elapsed / 2600);

            for (const flake of flakes) {
                flake.velocityX *= 0.985;
                flake.velocityY = flake.velocityY * 0.985 + 0.32;
                flake.x += flake.velocityX;
                flake.y += flake.velocityY;
                flake.rotation += flake.spin;
                context!.save();
                context!.translate(flake.x, flake.y);
                context!.rotate(flake.rotation);
                context!.fillStyle = flake.color;

                if (flake.shape === 'strip') {
                    context!.fillRect(
                        -flake.size / 2,
                        -flake.size / 5,
                        flake.size,
                        flake.size / 2.5,
                    );
                } else {
                    context!.beginPath();
                    context!.arc(0, 0, flake.size / 3, 0, Math.PI * 2);
                    context!.fill();
                }

                context!.restore();
            }

            if (elapsed < 2600) {
                requestAnimationFrame(frame);
            } else {
                canvas.remove();
            }
        }

        requestAnimationFrame(frame);
    }

    return { burst };
}
